(() => {
    "use strict";
    const root = document.getElementById("teamChat");
    if (!root) return;
    const $ = (id) => document.getElementById(id),
        base = root.dataset.base;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    let config,
        conversations = [],
        active = null,
        messages = [],
        hasOlder = false,
        version = 0;
    let conversationPages = 1,
        conversationMore = false,
        peoplePage = 1,
        peopleMore = false;
    let connected = false,
        busy = false,
        refreshing = false,
        lastTyping = 0,
        typingTimer,
        peopleTimer,
        pending;
    const selectedPeople = new Set();
    const modal = new bootstrap.Modal($("chatNewModal"));
    function clientId() {
        const bytes = crypto.getRandomValues(new Uint8Array(16));
        bytes[6] = (bytes[6] & 15) | 64;
        bytes[8] = (bytes[8] & 63) | 128;
        const hex = Array.from(bytes, (byte) =>
            byte.toString(16).padStart(2, "0")
        ).join("");
        return [
            hex.slice(0, 8),
            hex.slice(8, 12),
            hex.slice(12, 16),
            hex.slice(16, 20),
            hex.slice(20),
        ].join("-");
    }
    async function api(path, body) {
        const response = await fetch(base + "/" + path, {
            method: body === undefined ? "GET" : "POST",
            credentials: "same-origin",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrf,
            },
            ...(body === undefined ? {} : { body: JSON.stringify(body) }),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok)
            throw new Error(
                Object.values(data.errors || {}).flat()[0] ||
                    data.message ||
                    "Unable to complete this request."
            );
        return data;
    }
    function error(message, id = "chatError") {
        $(id).textContent = message || "";
        $(id).hidden = !message;
    }
    function node(tag, className, text) {
        const result = document.createElement(tag);
        result.className = className || "";
        if (text !== undefined) result.textContent = text;
        return result;
    }
    function renderConversations() {
        const list = $("chatConversations");
        list.replaceChildren();
        const search = $("chatSearch").value.toLowerCase();
        conversations
            .filter((c) => c.name.toLowerCase().includes(search))
            .forEach((c) => {
                const button = node(
                    "button",
                    "chat-conversation" +
                        (c.id === active?.id ? " is-active" : "")
                );
                button.type = "button";
                const top = node("span", "chat-conversation-top");
                top.append(node("strong", "", c.name));
                if (c.unread_count)
                    top.append(node("span", "chat-unread", c.unread_count));
                button.append(
                    top,
                    node(
                        "span",
                        "chat-conversation-preview",
                        c.latest_message?.body || "Start the conversation"
                    )
                );
                button.onclick = () => select(c);
                list.append(button);
            });
        if (!list.children.length)
            list.append(
                node(
                    "p",
                    "text-muted small p-2",
                    "No conversations yet. Start a new chat."
                )
            );
        $("chatMoreConversations").hidden = !conversationMore;
    }
    async function loadConversations() {
        const loaded = [];
        for (let page = 1; page <= conversationPages; page++) {
            const result = await api("conversations?page=" + page);
            loaded.push(...result.data);
            conversationMore = !!result.next_page_url;
            if (!conversationMore) break;
        }
        conversations = loaded;
        if (active)
            active = conversations.find((c) => c.id === active.id) || active;
        renderConversations();
        if (active) {
            $("chatMembers").textContent = active.members
                .map((m) => m.name)
                .join(", ");
            renderMessages(false);
        }
    }
    function renderMessages(scroll) {
        const list = $("chatMessages"),
            oldTop = list.scrollTop,
            wasNearBottom =
                list.scrollHeight - list.scrollTop - list.clientHeight < 80;
        list.replaceChildren();
        if (hasOlder) {
            const button = node(
                "button",
                "btn btn-outline-secondary btn-sm chat-load-older",
                "Load older messages"
            );
            button.type = "button";
            button.onclick = loadOlder;
            list.append(button);
        }
        messages.forEach((message) => {
            const own = message.sender_id === config.user_id,
                bubble = node(
                    "article",
                    "chat-message" + (own ? " is-own" : "")
                );
            bubble.append(
                node(
                    "span",
                    "chat-message-sender",
                    own ? "You" : message.sender_name
                ),
                node("div", "chat-message-body", message.body)
            );
            const readers = own
                ? active.members.filter(
                      (m) =>
                          m.id !== config.user_id &&
                          m.last_read_message_id >= message.id
                  ).length
                : 0;
            const receipt = own
                ? readers
                    ? ` | Read by ${readers}`
                    : " | Sent"
                : "";
            bubble.append(
                node(
                    "span",
                    "chat-message-meta",
                    new Date(message.created_at).toLocaleString() + receipt
                )
            );
            list.append(bubble);
        });
        if (!messages.length)
            list.append(
                node("p", "text-muted small", "No messages yet. Say hello.")
            );
        list.scrollTop = scroll || wasNearBottom ? list.scrollHeight : oldTop;
    }
    async function markRead() {
        if (
            !active ||
            !messages.length ||
            document.hidden ||
            !document.hasFocus()
        )
            return;
        const conversation = active,
            last = messages.at(-1).id;
        const me = conversation.members.find((m) => m.id === config.user_id);
        if ((me?.last_read_message_id || 0) >= last) return;
        await api(`conversations/${conversation.id}/read`, {
            message_id: last,
        });
        if (typeof window.fetchNotifications === "function")
            window.fetchNotifications();
        if (me) me.last_read_message_id = last;
        conversation.unread_count = 0;
        renderConversations();
    }
    async function select(conversation) {
        active = conversation;
        if (window.CrmChatRealtime)
            window.CrmChatRealtime.activeConversationId = conversation.id;
        messages = [];
        hasOlder = false;
        pending = null;
        version++;
        $("chatTitle").textContent = conversation.name;
        $("chatMembers").textContent = conversation.members
            .map((m) => m.name)
            .join(", ");
        $("chatBody").disabled = $("chatSend").disabled = false;
        $("chatBody").value = "";
        $("chatTyping").textContent = "";
        error("");
        renderConversations();
        renderMessages(true);
        await loadMessages(true).catch((e) => error(e.message));
    }
    async function loadMessages(initial = false) {
        if (!active) return;
        const id = active.id,
            snapshot = version;
        let cursor = initial ? undefined : messages.at(-1)?.id || 0,
            more;
        do {
            const result = await api(
                `conversations/${id}/messages` +
                    (cursor === undefined ? "" : "?after=" + cursor)
            );
            if (snapshot !== version) return;
            if (cursor === undefined) {
                messages = result.data;
                hasOlder = result.has_more;
            } else {
                const merged = new Map(messages.map((m) => [m.id, m]));
                result.data.forEach((m) => merged.set(m.id, m));
                messages = Array.from(merged.values()).sort(
                    (a, b) => a.id - b.id
                );
            }
            more = cursor !== undefined && result.has_more;
            cursor = messages.at(-1)?.id || 0;
            renderMessages(initial);
        } while (more);
        await markRead();
    }
    async function loadOlder() {
        if (!messages.length || !active) return;
        const snapshot = version,
            list = $("chatMessages"),
            oldHeight = list.scrollHeight,
            oldTop = list.scrollTop;
        try {
            const result = await api(
                `conversations/${active.id}/messages?before=${messages[0].id}`
            );
            if (snapshot !== version) return;
            const merged = new Map(
                [...result.data, ...messages].map((m) => [m.id, m])
            );
            messages = Array.from(merged.values()).sort((a, b) => a.id - b.id);
            hasOlder = result.has_more;
            renderMessages(false);
            list.scrollTop = list.scrollHeight - oldHeight + oldTop;
        } catch (e) {
            error(e.message);
        }
    }
    async function refresh() {
        if (refreshing || document.hidden) return;
        refreshing = true;
        try {
            await loadConversations();
            await loadMessages();
            error("");
        } catch (e) {
            error(e.message);
        } finally {
            refreshing = false;
        }
    }
    function receive(event) {
        if (event.kind === "typing") {
            if (
                active?.id === event.conversation_id &&
                event.user_id !== config.user_id
            ) {
                $("chatTyping").textContent = event.name + " is typing...";
                clearTimeout(typingTimer);
                typingTimer = setTimeout(
                    () => ($("chatTyping").textContent = ""),
                    3500
                );
            }
            return;
        }
        refresh();
    }
    async function loadPeople(reset = false) {
        if (reset) {
            peoplePage = 1;
            $("chatPeople").replaceChildren();
        }
        const query = $("chatPeopleSearch").value,
            snapshot = peoplePage;
        const result = await api(
            `users?page=${peoplePage}&search=${encodeURIComponent(query)}`
        );
        if (query !== $("chatPeopleSearch").value || snapshot !== peoplePage)
            return;
        peopleMore = !!result.next_page_url;
        $("chatMorePeople").hidden = !peopleMore;
        result.data.forEach((person) => {
            const label = node("label", "chat-person"),
                input = document.createElement("input");
            input.type =
                $("chatKind").value === "direct" ? "radio" : "checkbox";
            input.name = "chatPerson";
            input.value = person.id;
            input.checked = selectedPeople.has(person.id);
            input.onchange = () => {
                if ($("chatKind").value === "direct") selectedPeople.clear();
                if (input.checked) selectedPeople.add(person.id);
                else selectedPeople.delete(person.id);
            };
            label.append(input, document.createTextNode(person.name));
            $("chatPeople").append(label);
        });
        if (reset && !result.data.length)
            $("chatPeople").append(
                node("p", "text-muted small", "No team members found.")
            );
    }
    $("chatNew").onclick = () => {
        selectedPeople.clear();
        $("chatPeopleSearch").value = "";
        error("", "chatCreateError");
        modal.show();
        loadPeople(true).catch((e) => error(e.message, "chatCreateError"));
    };
    $("chatKind").onchange = () => {
        selectedPeople.clear();
        $("chatGroupNameWrap").hidden = $("chatKind").value !== "group";
        loadPeople(true).catch((e) => error(e.message, "chatCreateError"));
    };
    $("chatPeopleSearch").oninput = () => {
        clearTimeout(peopleTimer);
        peopleTimer = setTimeout(
            () =>
                loadPeople(true).catch((e) =>
                    error(e.message, "chatCreateError")
                ),
            250
        );
    };
    $("chatMorePeople").onclick = () => {
        peoplePage++;
        loadPeople().catch((e) => error(e.message, "chatCreateError"));
    };
    $("chatMoreConversations").onclick = () => {
        conversationPages++;
        loadConversations().catch((e) => error(e.message));
    };
    $("chatSearch").oninput = renderConversations;
    $("chatCreateForm").onsubmit = async (event) => {
        event.preventDefault();
        $("chatCreate").disabled = true;
        try {
            const conversation = await api("conversations", {
                type: $("chatKind").value,
                name: $("chatGroupName").value,
                member_ids: [...selectedPeople],
            });
            modal.hide();
            await loadConversations();
            await select(conversation);
        } catch (e) {
            error(e.message, "chatCreateError");
        } finally {
            $("chatCreate").disabled = false;
        }
    };
    $("chatComposer").onsubmit = async (event) => {
        event.preventDefault();
        if (busy || !active || !$("chatBody").value.trim()) return;
        const id = active.id,
            snapshot = version,
            body = $("chatBody").value.trim();
        if (!pending || pending.body !== body || pending.id !== id)
            pending = { id, body, client_id: clientId() };
        busy = true;
        $("chatSend").disabled = true;
        error("");
        try {
            await api(`conversations/${id}/messages`, {
                body,
                client_id: pending.client_id,
            });
            pending = null;
            if (snapshot === version) {
                $("chatBody").value = "";
                await loadMessages();
                $("chatMessages").scrollTop = $("chatMessages").scrollHeight;
            }
            await loadConversations();
        } catch (e) {
            error(e.message);
        } finally {
            busy = false;
            $("chatSend").disabled = !active;
        }
    };
    $("chatBody").onkeydown = (event) => {
        if (event.key === "Enter" && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            $("chatComposer").requestSubmit();
        }
    };
    $("chatBody").oninput = () => {
        if (active && Date.now() - lastTyping > 3000) {
            lastTyping = Date.now();
            api(`conversations/${active.id}/typing`, {}).catch(() => {});
        }
    };
    async function init() {
        try {
            config = window.CrmChatRealtime
                ? await window.CrmChatRealtime.ready
                : await api("config");
            if (!config)
                throw new Error(
                    "Chat is unavailable. Check the server configuration."
                );
            $("chatKind").querySelector('[value="group"]').hidden =
                !config.can_create_group;
            await loadConversations();
            $("chatConnection").textContent = "Polling every 15 seconds";
            if (window.CrmChatRealtime) {
                window.CrmChatRealtime.subscribe(receive);
                window.CrmChatRealtime.onState((state) => {
                    connected = state.connected;
                    $("chatConnection").textContent = state.status;
                });
            }
            const requested = Number(
                new URL(window.location.href).searchParams.get("conversation")
            );
            if (requested > 0) {
                const conversation =
                    conversations.find((item) => item.id === requested) ||
                    (await api("conversations/" + requested));
                await select(conversation);
            }
            setInterval(() => {
                if (!connected) refresh();
            }, 15000);
            setInterval(refresh, 60000);
        } catch (e) {
            error(e.message);
            $("chatConnection").textContent = "Chat unavailable";
            $("chatNew").disabled = true;
        }
    }
    window.addEventListener("focus", refresh);
    document.addEventListener("visibilitychange", () => {
        if (!document.hidden) refresh();
    });
    init();
})();
