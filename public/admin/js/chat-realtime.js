(() => {
    "use strict";
    const script = document.getElementById("crmChatRealtimeScript");
    if (!script || window.CrmChatRealtime) return;
    const base = script.dataset.base,
        chatUrl = script.dataset.chatUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const listeners = new Set(),
        stateListeners = new Set(),
        seen = new Set();
    let config,
        echo,
        connected = false,
        status = "Connecting...",
        polling = false,
        initialized = false;
    const realtime = (window.CrmChatRealtime = {
        ready: null,
        activeConversationId: null,
        refreshUnread,
        subscribe(callback) {
            listeners.add(callback);
            return () => listeners.delete(callback);
        },
        onState(callback) {
            stateListeners.add(callback);
            callback({ connected, status });
            return () => stateListeners.delete(callback);
        },
    });
    function setState(live, description) {
        connected = live;
        status = description;
        stateListeners.forEach((callback) => callback({ connected, status }));
    }
    async function get(path) {
        const response = await fetch(base + "/" + path, {
            credentials: "same-origin",
            headers: { Accept: "application/json" },
        });
        if (!response.ok) throw new Error("Chat request failed");
        return response.json();
    }
    let unreadBusy = false,
        unreadAgain = false,
        audio,
        lastSound = 0;
    function unlockAudio() {
        try {
            const Audio = window.AudioContext || window.webkitAudioContext;
            if (!Audio) return;
            audio ||= new Audio();
            if (audio.state === "suspended") audio.resume().catch(() => {});
        } catch {}
    }
    function playSound() {
        if (Date.now() - lastSound < 800) return;
        unlockAudio();
        if (!audio || audio.state !== "running") return;
        lastSound = Date.now();
        const start = audio.currentTime;
        [740, 980].forEach((frequency, index) => {
            const oscillator = audio.createOscillator(),
                gain = audio.createGain(),
                time = start + index * 0.12;
            oscillator.type = "sine";
            oscillator.frequency.value = frequency;
            gain.gain.setValueAtTime(0, time);
            gain.gain.linearRampToValueAtTime(0.075, time + 0.012);
            gain.gain.exponentialRampToValueAtTime(0.001, time + 0.16);
            oscillator.connect(gain);
            gain.connect(audio.destination);
            oscillator.start(time);
            oscillator.stop(time + 0.18);
        });
    }
    document.addEventListener("pointerdown", unlockAudio, { passive: true });
    document.addEventListener("keydown", unlockAudio);
    async function acknowledge(conversationId, messageId) {
        await fetch(base + "/conversations/" + conversationId + "/delivered", {
            method: "POST",
            credentials: "same-origin",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrf,
            },
            body: JSON.stringify({ message_id: messageId }),
        });
    }
    async function refreshUnread() {
        if (!config) return;
        if (unreadBusy) {
            unreadAgain = true;
            return;
        }
        unreadBusy = true;
        try {
            const summary = await get("unread"),
                count = Number(summary.unread_count) || 0;
            const indicator = document.getElementById("messageIndicator"),
                badge = document.getElementById("messageCount");
            if (badge) {
                badge.textContent = count > 99 ? "99+" : String(count);
                badge.classList.toggle("show", count > 0);
                badge.hidden = count === 0;
            }
            if (indicator) {
                indicator.classList.toggle("has-unread", count > 0);
                indicator.setAttribute(
                    "aria-label",
                    count ? count + " unread messages" : "Messages"
                );
            }
            document
                .querySelectorAll(".sidebar-message-count")
                .forEach((element) => {
                    element.textContent = count > 99 ? "99+" : String(count);
                    element.classList.toggle("show", count > 0);
                    element.hidden = count === 0;
                });
            await Promise.all(
                (summary.conversations || [])
                    .filter(
                        (item) =>
                            item.incoming_message_id >
                            item.last_delivered_message_id
                    )
                    .map((item) =>
                        acknowledge(
                            item.conversation_id,
                            item.incoming_message_id
                        ).catch(() => {})
                    )
            );
        } catch {
        } finally {
            unreadBusy = false;
            if (unreadAgain) {
                unreadAgain = false;
                refreshUnread();
            }
        }
    }
    function alertMessage(event) {
        if (event.sender_id === config.user_id || seen.has(event.message_id))
            return;
        seen.add(event.message_id);
        if (seen.size > 1000) seen.delete(seen.values().next().value);
        playSound();
        if (
            realtime.activeConversationId === event.conversation_id &&
            !document.hidden &&
            document.hasFocus()
        )
            return;
        let container = document.getElementById("crmChatAlerts");
        if (!container) {
            container = document.createElement("div");
            container.id = "crmChatAlerts";
            container.className =
                "toast-container position-fixed bottom-0 end-0 p-3";
            container.style.zIndex = "1090";
            container.setAttribute("aria-live", "polite");
            document.body.append(container);
        }
        while (container.children.length >= 3)
            container.firstElementChild.remove();
        const toast = document.createElement("div");
        toast.className = "toast";
        toast.setAttribute("role", "status");
        const header = document.createElement("div");
        header.className = "toast-header";
        const title = document.createElement("strong");
        title.className = "me-auto";
        title.textContent = event.sender_name || "New team message";
        const close = document.createElement("button");
        close.type = "button";
        close.className = "btn-close";
        close.setAttribute("aria-label", "Dismiss message");
        close.dataset.bsDismiss = "toast";
        header.append(title, close);
        const body = document.createElement("div");
        body.className = "toast-body";
        const preview = document.createElement("p");
        preview.className = "mb-2";
        preview.textContent = event.preview || "You have a new chat message.";
        const link = document.createElement("a");
        link.className = "btn btn-sm btn-outline-primary";
        link.textContent = "Open chat";
        link.href =
            chatUrl +
            "?conversation=" +
            encodeURIComponent(event.conversation_id);
        body.append(preview, link);
        toast.append(header, body);
        container.append(toast);
        toast.addEventListener("hidden.bs.toast", () => toast.remove());
        new bootstrap.Toast(toast, { delay: 10000 }).show();
    }
    async function receive(event) {
        if (["message", "read"].includes(event.kind)) refreshUnread();
        if (
            ["message", "read"].includes(event.kind) &&
            typeof window.fetchNotifications === "function"
        ) {
            window.fetchNotifications();
        }
        listeners.forEach((callback) => callback(event));
        if (event.kind !== "message") return;
        if (!event.sender_id) {
            // Also support events from servers deployed before preview payloads were added.
            try {
                const result = await get(
                    `conversations/${
                        event.conversation_id
                    }/messages?after=${Math.max(0, event.message_id - 1)}`
                );
                const message = result.data.find(
                    (item) => item.id === event.message_id
                );
                if (message)
                    event = {
                        ...event,
                        sender_id: message.sender_id,
                        sender_name: message.sender_name,
                        preview: (
                            message.body ||
                            (message.attachment
                                ? "Shared " +
                                  message.attachment.kind +
                                  ": " +
                                  message.attachment.name
                                : "")
                        ).slice(0, 120),
                    };
            } catch {
                return;
            }
        }
        alertMessage(event);
    }
    async function poll() {
        if (!config) return;
        if (connected) {
            refreshUnread();
            return;
        }
        if (polling) return;
        polling = true;
        try {
            const result = await get("conversations");
            await refreshUnread();
            if (typeof window.fetchNotifications === "function")
                window.fetchNotifications();
            for (const conversation of result.data) {
                const message = conversation.latest_message;
                if (!message) continue;
                if (!initialized) seen.add(message.id);
                else if (conversation.unread_count)
                    alertMessage({
                        conversation_id: conversation.id,
                        message_id: message.id,
                        sender_id: message.sender_id,
                        sender_name: message.sender_name,
                        preview: message.body.slice(0, 120),
                    });
            }
            initialized = true;
        } catch {
            /* Preserve the connection status; retry on the next interval. */
        } finally {
            polling = false;
        }
    }
    realtime.ready = (async () => {
        try {
            config = await get("config");
            setState(
                false,
                config.realtime_enabled
                    ? "Connecting to live chat..."
                    : "Polling every 15 seconds | Pusher is not configured"
            );
            await poll();
            if (config.realtime_enabled && window.Pusher && window.Echo) {
                const EchoClient =
                    typeof window.Echo === "function"
                        ? window.Echo
                        : window.Echo.default;
                echo = new EchoClient({
                    broadcaster: "pusher",
                    key: config.key,
                    cluster: config.cluster,
                    forceTLS: true,
                    authEndpoint: base + "/broadcasting/auth",
                    auth: { headers: { "X-CSRF-TOKEN": csrf } },
                });
                echo.private("chat.user." + config.user_id)
                    .listen(".chat.updated", receive)
                    .subscribed(async () => {
                        await poll();
                        setState(true, "Live connection");
                        listeners.forEach((callback) =>
                            callback({ kind: "reconnected" })
                        );
                    })
                    .error(() =>
                        setState(false, "Subscription failed | Polling enabled")
                    );
                echo.connector.pusher.connection.bind(
                    "state_change",
                    (state) => {
                        if (state.current !== "connected")
                            setState(false, "Reconnecting | Polling enabled");
                    }
                );
                echo.connector.pusher.connection.bind("error", () =>
                    setState(false, "Connection failed | Polling enabled")
                );
            } else if (config.realtime_enabled)
                setState(false, "Chat client unavailable | Polling enabled");
            setInterval(poll, 15000);
            return config;
        } catch {
            setState(false, "Chat unavailable");
            return null;
        }
    })();
    window.addEventListener("focus", poll);
    document.addEventListener("visibilitychange", () => {
        if (!document.hidden) poll();
    });
    window.addEventListener("pagehide", () => echo?.disconnect());
    window.addEventListener("pageshow", (event) => {
        if (event.persisted) echo?.connector.pusher.connect();
    });
})();
