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
        refreshAgain = false,
        lastTyping = 0,
        typingTimer,
        peopleTimer,
        pending;
    let selectedFile = null,
        recorder = null,
        recordingStream = null,
        recordingTimer,
        recordingDiscard = false;
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
                ...(body instanceof FormData
                    ? {}
                    : { "Content-Type": "application/json" }),
                "X-CSRF-TOKEN": csrf,
            },
            ...(body === undefined
                ? {}
                : {
                      body:
                          body instanceof FormData
                              ? body
                              : JSON.stringify(body),
                  }),
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
    function contactAvatar(name, url) {
        const avatar = node(
            "span",
            "chat-contact-avatar",
            (Array.from((name || "?").trim())[0] || "?").toLocaleUpperCase()
        );
        avatar.setAttribute(
            "aria-label",
            (name || "User") + " profile picture"
        );
        if (url) {
            const image = node("img", "");
            image.alt = name || "Profile picture";
            image.onerror = () => image.remove();
            image.src = url;
            avatar.append(image);
        }
        return avatar;
    }
    function renderHeader() {
        if (!active) return;
        $("chatTitle").textContent = active.name;
        $("chatMembers").textContent = active.members
            .map((member) => member.name)
            .join(", ");
        const other = active.members.find(
            (member) => member.id !== config.user_id
        );
        $("chatThreadAvatar").hidden = false;
        $("chatThreadAvatar").replaceChildren(
            contactAvatar(
                active.name,
                active.type === "direct" ? other?.avatar_url : null
            )
        );
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
                        (c.id === active?.id ? " is-active" : "") +
                        (c.unread_count ? " has-unread" : "")
                );
                button.type = "button";
                const other = c.members.find(
                    (member) => member.id !== config.user_id
                );
                const avatar = contactAvatar(
                    c.name,
                    c.type === "direct" ? other?.avatar_url : null
                );
                const copy = node("span", "chat-conversation-copy");
                const top = node("span", "chat-conversation-top");
                top.append(node("strong", "", c.name));
                if (c.unread_count)
                    top.append(node("span", "chat-unread", c.unread_count));
                copy.append(
                    top,
                    node(
                        "span",
                        "chat-conversation-preview",
                        c.latest_message?.body ||
                            (c.latest_message?.attachment
                                ? "Shared " + c.latest_message.attachment.kind
                                : "Start the conversation")
                    )
                );
                button.append(avatar, copy);
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
            renderHeader();
            renderMessages(false);
        }
    }
    function renderMessages(scroll) {
        const list = $("chatMessages"),
            oldTop = list.scrollTop,
            wasNearBottom =
                list.scrollHeight - list.scrollTop - list.clientHeight < 80;
        const signature = JSON.stringify([
            messages,
            active?.id,
            active?.members,
            hasOlder,
        ]);
        if (list.dataset.signature === signature) {
            if (scroll) list.scrollTop = list.scrollHeight;
            return;
        }
        list.dataset.signature = signature;
        const playback = new Map(
            Array.from(
                list.querySelectorAll("audio[data-message-id]"),
                (audio) => [
                    audio.dataset.messageId,
                    { time: audio.currentTime, playing: !audio.paused },
                ]
            )
        );
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
            if (message.attachment) {
                const attachment = message.attachment;
                if (attachment.kind === "image") {
                    const image = node("img", "chat-shared-image");
                    image.src = attachment.url;
                    image.alt = attachment.name;
                    image.loading = "lazy";
                    bubble.append(image);
                } else if (attachment.kind === "audio") {
                    const audio = node("audio", "chat-shared-audio");
                    audio.controls = true;
                    audio.preload = "none";
                    audio.src = attachment.url;
                    audio.dataset.messageId = message.id;
                    const previous = playback.get(String(message.id));
                    if (previous) {
                        audio.addEventListener(
                            "loadedmetadata",
                            () => {
                                audio.currentTime = previous.time;
                                if (previous.playing)
                                    audio.play().catch(() => {});
                            },
                            { once: true }
                        );
                        audio.preload = "metadata";
                    }
                    bubble.append(audio);
                }
                const link = node(
                    "a",
                    "chat-file-link",
                    attachment.kind === "pdf"
                        ? "PDF: " + attachment.name
                        : attachment.name
                );
                link.href = attachment.url;
                link.target = "_blank";
                link.rel = "noopener";
                bubble.append(link);
            }
            const meta = node(
                "span",
                "chat-message-meta",
                new Date(message.created_at).toLocaleString()
            );
            if (own) {
                const others = active.members.filter(
                    (member) => member.id !== config.user_id
                );
                const readers = others.filter(
                    (member) => member.last_read_message_id >= message.id
                ).length;
                const delivered = others.filter(
                    (member) =>
                        Math.max(
                            member.last_delivered_message_id || 0,
                            member.last_read_message_id
                        ) >= message.id
                ).length;
                const allRead = others.length > 0 && readers === others.length,
                    allDelivered =
                        others.length > 0 && delivered === others.length;
                const label = allRead
                    ? "Read"
                    : allDelivered
                    ? "Received"
                    : "Sent";
                const receipt = node(
                    "span",
                    "chat-receipt" + (allRead ? " is-read" : "")
                );
                receipt.setAttribute("aria-label", label);
                receipt.title =
                    label +
                    " | Received by " +
                    delivered +
                    "/" +
                    others.length +
                    " | Read by " +
                    readers +
                    "/" +
                    others.length;
                receipt.innerHTML =
                    '<svg viewBox="0 0 24 16" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m2 8 4 4 9-9"/>' +
                    (allDelivered || allRead
                        ? '<path d="m10 10 2 2 9-9"/>'
                        : "") +
                    "</svg>";
                meta.append(receipt);
            }
            bubble.append(meta);
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
        window.CrmChatRealtime?.refreshUnread();
        renderConversations();
    }
    async function select(conversation) {
        stopRecording(true);
        setAttachment(null);
        active = conversation;
        if (window.CrmChatRealtime)
            window.CrmChatRealtime.activeConversationId = conversation.id;
        messages = [];
        hasOlder = false;
        pending = null;
        version++;
        renderHeader();
        $("chatTitle").textContent = conversation.name;
        $("chatMembers").textContent = conversation.members
            .map((m) => m.name)
            .join(", ");
        $("chatBody").disabled =
            $("chatSend").disabled =
            $("chatAttach").disabled =
            $("chatRecord").disabled =
                false;
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
        if (document.hidden) return;
        if (refreshing) {
            refreshAgain = true;
            return;
        }
        refreshing = true;
        try {
            await loadConversations();
            await loadMessages();
            error("");
        } catch (e) {
            error(e.message);
        } finally {
            refreshing = false;
            if (refreshAgain) {
                refreshAgain = false;
                refresh();
            }
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
        if (
            busy ||
            !active ||
            recorder?.state === "recording" ||
            (!$("chatBody").value.trim() && !selectedFile)
        )
            return;
        const id = active.id,
            snapshot = version,
            body = $("chatBody").value.trim();
        if (
            !pending ||
            pending.body !== body ||
            pending.id !== id ||
            pending.file !== selectedFile
        )
            pending = { id, body, client_id: clientId(), file: selectedFile };
        busy = true;
        $("chatSend").disabled = true;
        error("");
        try {
            const payload = selectedFile
                ? new FormData()
                : { body, client_id: pending.client_id };
            if (payload instanceof FormData) {
                payload.append("body", body);
                payload.append("client_id", pending.client_id);
                payload.append("file", selectedFile);
            }
            await api(`conversations/${id}/messages`, payload);
            pending = null;
            if (snapshot === version) {
                $("chatBody").value = "";
                setAttachment(null);
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
    function setAttachment(file) {
        if (file && file.size > (config?.max_attachment_bytes || 10485760)) {
            error("Choose a file smaller than 10 MB.");
            return;
        }
        selectedFile = file;
        $("chatAttachmentPreview").hidden = !file;
        $("chatAttachmentName").textContent = file
            ? file.name + " (" + (file.size / 1024).toFixed(0) + " KB)"
            : "";
        if (!file) $("chatFile").value = "";
    }
    $("chatAttach").onclick = () => {
        if (active && !busy) $("chatFile").click();
    };
    $("chatFile").onchange = () =>
        setAttachment($("chatFile").files[0] || null);
    $("chatRemoveAttachment").onclick = () => setAttachment(null);
    function stopRecording(discard = false) {
        recordingDiscard = discard;
        clearInterval(recordingTimer);
        if (recorder?.state === "recording") recorder.stop();
        recordingStream?.getTracks().forEach((track) => track.stop());
        $("chatRecordingStatus").hidden = true;
        $("chatRecord").innerHTML = '<i class="bi bi-mic"></i>';
        $("chatRecord").setAttribute("aria-label", "Record voice message");
        $("chatSend").disabled = busy || !active;
    }
    $("chatRecord").onclick = async () => {
        if (recorder?.state === "recording") {
            stopRecording();
            return;
        }
        if (!active || busy) return;
        if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) {
            error(
                "Voice recording needs HTTPS and browser microphone support. You can also attach an audio file."
            );
            return;
        }
        const recordingVersion = version;
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: true,
            });
            if (recordingVersion !== version) {
                stream.getTracks().forEach((track) => track.stop());
                return;
            }
            recordingStream = stream;
            recordingDiscard = false;
            const mime = [
                "audio/webm;codecs=opus",
                "audio/ogg;codecs=opus",
                "audio/mp4",
            ].find((type) => MediaRecorder.isTypeSupported(type));
            recorder = new MediaRecorder(
                stream,
                mime ? { mimeType: mime } : undefined
            );
            const chunks = [],
                recording = recorder;
            recording.ondataavailable = (event) => {
                if (event.data.size) chunks.push(event.data);
            };
            recording.onstop = () => {
                if (!recordingDiscard && recordingVersion === version) {
                    const type = (recording.mimeType || "audio/webm").split(
                            ";"
                        )[0],
                        extension = type.includes("mp4")
                            ? "m4a"
                            : type.includes("ogg")
                            ? "ogg"
                            : "webm";
                    setAttachment(
                        new File(
                            chunks,
                            "voice-" + Date.now() + "." + extension,
                            { type }
                        )
                    );
                }
                stream.getTracks().forEach((track) => track.stop());
            };
            recorder.start(1000);
            let elapsed = 0;
            const recordingStarted = Date.now();
            $("chatRecordingStatus").hidden = false;
            $("chatRecordingStatus").textContent =
                "Recording 0:00 | click Stop to attach";
            $("chatRecord").innerHTML = '<i class="bi bi-stop-fill"></i>';
            $("chatRecord").setAttribute("aria-label", "Stop voice recording");
            $("chatSend").disabled = true;
            recordingTimer = setInterval(() => {
                elapsed = Math.floor((Date.now() - recordingStarted) / 1000);
                $("chatRecordingStatus").textContent =
                    "Recording " +
                    Math.floor(elapsed / 60) +
                    ":" +
                    String(elapsed % 60).padStart(2, "0") +
                    " | click Stop to attach";
                if (elapsed >= 120) stopRecording();
            }, 1000);
        } catch (e) {
            stopRecording(true);
            error(
                e.name === "NotAllowedError"
                    ? "Microphone permission was denied. Allow it in browser settings or attach an audio file."
                    : e.message
            );
        }
    };
    function showProfilePicture(url) {
        if (!url) return;
        [
            $("chatProfileButton"),
            ...document.querySelectorAll(".user-avatar,.user-dropdown-avatar"),
        ]
            .filter(Boolean)
            .forEach((element) => {
                const image = node("img", "chat-avatar");
                image.src = url;
                image.alt = "Your profile picture";
                element.replaceChildren(image);
            });
    }
    $("chatProfileButton").onclick = () => $("chatProfileFile").click();
    $("chatProfileFile").onchange = async () => {
        const file = $("chatProfileFile").files[0];
        if (!file) return;
        if (file.size > 2097152) {
            error("Profile pictures must be smaller than 2 MB.");
            return;
        }
        const form = new FormData();
        form.append("profile_pic", file);
        $("chatProfileButton").disabled = true;
        try {
            const result = await api("profile-picture", form);
            showProfilePicture(result.profile_picture_url);
            error("");
        } catch (e) {
            error(e.message);
        } finally {
            $("chatProfileButton").disabled = false;
            $("chatProfileFile").value = "";
        }
    };
    window.addEventListener("pagehide", () => stopRecording(true));
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
            showProfilePicture(config.profile_picture_url);
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
