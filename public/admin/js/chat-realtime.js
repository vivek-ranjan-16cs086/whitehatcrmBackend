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
    function alertMessage(event) {
        if (event.sender_id === config.user_id || seen.has(event.message_id))
            return;
        seen.add(event.message_id);
        if (seen.size > 1000) seen.delete(seen.values().next().value);
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
                        preview: message.body.slice(0, 120),
                    };
            } catch {
                return;
            }
        }
        alertMessage(event);
    }
    async function poll() {
        if (polling || connected || !config) return;
        polling = true;
        try {
            const result = await get("conversations");
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
