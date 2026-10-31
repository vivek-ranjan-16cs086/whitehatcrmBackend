import { test, expect } from "@playwright/test";
import { readFileSync, existsSync } from "node:fs";
import { resolve } from "node:path";

test.use({ channel: "chrome", viewport: { width: 1280, height: 900 } });

test("chat renders safely, sends messages, and starts groups using the actual UI", async ({
    page,
}) => {
    await page.addInitScript(() => {
        Object.defineProperty(navigator, "mediaDevices", {
            configurable: true,
            value: {
                getUserMedia: async () => ({
                    getTracks: () => [{ stop() {} }],
                }),
            },
        });
        window.MediaRecorder = class {
            state = "inactive";
            mimeType = "audio/webm";
            static isTypeSupported() {
                return true;
            }
            start() {
                this.state = "recording";
            }
            stop() {
                this.state = "inactive";
                this.ondataavailable({
                    data: new Blob(["audio-test"], { type: "audio/webm" }),
                });
                queueMicrotask(() => this.onstop());
            }
        };
    });
    const errors = [];
    page.on("pageerror", (error) => errors.push(error.message));
    const messages = [
        {
            id: 1,
            conversation_id: 1,
            sender_id: 2,
            sender_name: "Sales User",
            client_id: "00000000-0000-4000-8000-000000000001",
            body: "<script>window.chatInjected=true</script>",
            created_at: "2026-10-31T10:00:00Z",
        },
    ];
    const members = [
        { id: 1, name: "Admin", last_read_message_id: 0 },
        { id: 2, name: "Sales User", last_read_message_id: 0 },
    ];
    const conversations = [
        {
            id: 1,
            name: "Sales User",
            type: "direct",
            members,
            unread_count: 1,
            latest_message: messages[0],
        },
    ];
    let created;
    const source = readFileSync(
        "resources/views/admin/chat/index.blade.php",
        "utf8"
    );
    let content = source
        .split("@section('content')")[1]
        .split("@endsection")[0];
    content = content
        .replace(/<link[^>]+>/g, "")
        .replace("{{ url('admin/chat') }}", "/admin/chat");
    const html = `<!doctype html><html><head><meta name="csrf-token" content="test-token">
        <link rel="stylesheet" href="/admin/libraries/css/bootstrap.min.css"><link rel="stylesheet" href="/admin/css/admin.css">
        <link rel="stylesheet" href="/admin/css/theme.css"><link rel="stylesheet" href="/admin/css/chat.css"></head><body class="chat-page">
        ${content}<script src="/admin/libraries/js/bootstrap.bundle.min.js"></script><script id="crmChatRealtimeScript" data-base="/admin/chat" data-chat-url="/admin/chat" src="/admin/js/chat-realtime.js"></script><script src="/admin/js/chat.js"></script></body></html>`;
    await page.route("http://chat.test/**", async (route) => {
        const url = new URL(route.request().url()),
            path = url.pathname;
        const json = (data) => route.fulfill({ json: data });
        if (path === "/")
            return route.fulfill({ contentType: "text/html", body: html });
        if (path === "/admin/chat/profile-picture")
            return json({ profile_picture_url: "/test-avatar.png" });
        if (path === "/test-avatar.png" || path === "/test-attachment.png")
            return route.fulfill({
                contentType: "image/png",
                body: Buffer.from(
                    "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=",
                    "base64"
                ),
            });
        if (path === "/admin/chat/config")
            return json({
                user_id: 1,
                realtime_enabled: false,
                can_create_group: true,
            });
        if (path === "/admin/chat/unread")
            return json({
                unread_count: conversations.reduce(
                    (sum, item) => sum + item.unread_count,
                    0
                ),
                conversations: [],
            });
        if (path.endsWith("/delivered")) return json({ status: true });
        if (path === "/admin/chat/users")
            return json({
                data: [{ id: 2, name: "Sales User" }],
                next_page_url: null,
            });
        if (path === "/admin/chat/conversations") {
            if (route.request().method() === "POST") {
                created = route.request().postDataJSON();
                const conversation = {
                    id: 2,
                    name: created.name,
                    type: created.type,
                    members,
                    unread_count: 0,
                    latest_message: null,
                };
                conversations.push(conversation);
                return json(conversation);
            }
            return json({ data: conversations, next_page_url: null });
        }
        if (path.endsWith("/messages")) {
            if (path.includes("/2/"))
                return json({ data: [], has_more: false });
            if (route.request().method() === "POST") {
                let input;
                if (
                    route
                        .request()
                        .headers()
                        ["content-type"].includes("multipart/form-data")
                ) {
                    const raw = route
                        .request()
                        .postDataBuffer()
                        .toString("latin1");
                    const filename = raw.match(/filename="([^"]+)"/)[1];
                    input = {
                        body: "",
                        client_id: raw.match(
                            /name="client_id"\r\n\r\n([^\r]+)/
                        )[1],
                        attachment: {
                            name: filename,
                            kind: filename.endsWith(".webm")
                                ? "audio"
                                : "image",
                            url: filename.endsWith(".webm")
                                ? "/test-audio.webm"
                                : "/test-attachment.png",
                        },
                    };
                } else input = route.request().postDataJSON();
                expect(input.client_id).toMatch(/^[\da-f-]{36}$/);
                const message = {
                    ...messages[0],
                    ...input,
                    id: messages.length + 1,
                    sender_id: 1,
                    sender_name: "Admin",
                };
                messages.push(message);
                conversations[0].latest_message = message;
                return json(message);
            }
            return json({
                data: messages.filter(
                    (message) =>
                        message.id > Number(url.searchParams.get("after") || 0)
                ),
                has_more: false,
            });
        }
        if (path.endsWith("/read")) {
            members[0].last_read_message_id = route
                .request()
                .postDataJSON().message_id;
            conversations[0].unread_count = 0;
            return json({ status: true });
        }
        if (path.endsWith("/typing")) return json({ status: true });
        const file = resolve("public", "." + path);
        if (existsSync(file)) return route.fulfill({ path: file });
        return route.abort();
    });
    await page.goto("http://chat.test/");
    await page.getByRole("button", { name: /Sales User/ }).click();
    await expect(page.locator(".chat-message-body")).toHaveText(
        messages[0].body
    );
    expect(await page.evaluate(() => window.chatInjected)).toBeUndefined();
    await page
        .getByRole("textbox", { name: "Message", exact: true })
        .fill("Hello from the web CRM");
    await page.getByRole("button", { name: "Send", exact: true }).click();
    await expect(page.locator(".chat-message-body").last()).toHaveText(
        "Hello from the web CRM"
    );
    await expect(page.locator("#chatBody")).toHaveValue("");
    await expect(page.locator(".chat-receipt")).toHaveAttribute(
        "aria-label",
        "Sent"
    );
    members[1].last_delivered_message_id = 2;
    await page.evaluate(() => window.dispatchEvent(new Event("focus")));
    await expect(page.locator(".chat-receipt")).toHaveAttribute(
        "aria-label",
        "Received"
    );
    members[1].last_read_message_id = 2;
    await page.evaluate(() => window.dispatchEvent(new Event("focus")));
    await expect(page.locator(".chat-receipt")).toHaveAttribute(
        "aria-label",
        "Read"
    );
    await expect(page.locator(".chat-receipt")).toHaveClass(/is-read/);
    await page
        .locator("#chatProfileFile")
        .setInputFiles({
            name: "avatar.png",
            mimeType: "image/png",
            buffer: Buffer.from("png-test"),
        });
    await expect(page.locator("#chatProfileButton img")).toHaveAttribute(
        "src",
        "/test-avatar.png"
    );
    await page
        .locator("#chatFile")
        .setInputFiles({
            name: "photo.png",
            mimeType: "image/png",
            buffer: Buffer.from("png-test"),
        });
    await expect(page.locator("#chatAttachmentPreview")).toBeVisible();
    await page.getByRole("button", { name: "Send", exact: true }).click();
    await expect(page.locator(".chat-shared-image")).toHaveAttribute(
        "src",
        "/test-attachment.png"
    );
    await page
        .getByRole("button", { name: "Record voice message", exact: true })
        .click();
    await expect(page.locator("#chatRecordingStatus")).toBeVisible();
    await page
        .getByRole("button", { name: "Stop voice recording", exact: true })
        .click();
    await expect(page.locator("#chatAttachmentName")).toContainText("voice-");
    await page.getByRole("button", { name: "Send", exact: true }).click();
    await expect(page.locator(".chat-shared-audio")).toHaveCount(1);
    await page.getByRole("button", { name: "New chat" }).click();
    await page.locator("#chatKind").selectOption("group");
    await page.getByLabel("Group name").fill("Sales Team");
    await page.getByLabel("Sales User", { exact: true }).check();
    await page.getByRole("button", { name: "Start chat", exact: true }).click();
    await expect(page.locator("#chatTitle")).toHaveText("Sales Team");
    expect(created).toEqual({
        type: "group",
        name: "Sales Team",
        member_ids: [2],
    });
    expect(errors).toEqual([]);
});

test("bundled Echo exposes a working constructor for the Pusher client", async ({
    page,
}) => {
    await page.addScriptTag({ path: "public/admin/libraries/js/echo.iife.js" });
    expect(
        await page.evaluate(
            () =>
                typeof (typeof window.Echo === "function"
                    ? window.Echo
                    : window.Echo.default)
        )
    ).toBe("function");
});

test("a live message displays a notification on another CRM page without waiting for polling", async ({
    page,
}) => {
    await page.addInitScript(() => {
        window.chatSoundPlays = 0;
        window.AudioContext = class {
            state = "running";
            currentTime = 0;
            destination = {};
            createOscillator() {
                return {
                    type: "",
                    frequency: { value: 0 },
                    connect() {},
                    start() {
                        window.chatSoundPlays++;
                    },
                    stop() {},
                };
            }
            createGain() {
                return {
                    gain: {
                        setValueAtTime() {},
                        linearRampToValueAtTime() {},
                        exponentialRampToValueAtTime() {},
                    },
                    connect() {},
                };
            }
        };
        window.notificationRefreshes = 0;
        window.fetchNotifications = () => {
            window.notificationRefreshes++;
        };
        window.Pusher = function () {};
        window.Echo = class {
            connector = { pusher: { connection: { bind() {} }, connect() {} } };
            private() {
                const channel = {
                    listen(name, receive) {
                        window.testChatReceive = receive;
                        return channel;
                    },
                    subscribed(callback) {
                        queueMicrotask(callback);
                        return channel;
                    },
                    error() {
                        return channel;
                    },
                };
                return channel;
            }
            disconnect() {}
        };
    });
    await page.route("http://chat.test/**", async (route) => {
        const path = new URL(route.request().url()).pathname;
        if (path === "/")
            return route.fulfill({
                contentType: "text/html",
                body: `<!doctype html><html><head><meta name="csrf-token" content="test"></head><body>
            <h1>All Leads</h1><a id="messageIndicator" aria-label="Messages"><span id="messageCount"></span></a><button id="messageSoundToggle" type="button">Sound</button><script src="/admin/libraries/js/bootstrap.bundle.min.js"></script>
            <script id="crmChatRealtimeScript" data-base="/admin/chat" data-chat-url="/admin/chat" src="/admin/js/chat-realtime.js"></script></body></html>`,
            });
        if (path === "/admin/chat/config")
            return route.fulfill({
                json: {
                    user_id: 1,
                    realtime_enabled: true,
                    key: "test",
                    cluster: "ap2",
                },
            });
        if (path === "/admin/chat/unread")
            return route.fulfill({
                json: {
                    unread_count: 3,
                    conversations: [
                        {
                            conversation_id: 7,
                            incoming_message_id: 10,
                            last_delivered_message_id: 0,
                        },
                    ],
                },
            });
        if (path.endsWith("/delivered"))
            return route.fulfill({ json: { status: true } });
        if (path === "/admin/chat/conversations")
            return route.fulfill({ json: { data: [], next_page_url: null } });
        const file = resolve("public", "." + path);
        if (existsSync(file)) return route.fulfill({ path: file });
        return route.abort();
    });
    await page.goto("http://chat.test/");
    await page.evaluate(async () => {
        await window.CrmChatRealtime.ready;
        window.CrmChatRealtime.onState(
            (state) => (window.chatLive = state.connected)
        );
    });
    await expect.poll(() => page.evaluate(() => window.chatLive)).toBe(true);
    await expect(page.locator("#messageCount")).toHaveText("3");
    await expect(page.locator("#messageIndicator")).toHaveClass(/has-unread/);
    await page.getByRole("heading", { name: "All Leads" }).click();
    await page.evaluate(() =>
        window.testChatReceive({
            kind: "message",
            message_id: 10,
            conversation_id: 7,
            sender_id: 2,
            sender_name: "Sales User",
            preview: "Please check this lead.",
        })
    );
    await expect(page.locator("#crmChatAlerts .toast")).toHaveCount(1);
    expect(await page.evaluate(() => window.chatSoundPlays)).toBeGreaterThan(0);
    expect(
        await page.evaluate(() => window.notificationRefreshes)
    ).toBeGreaterThan(1);
    await expect(page.locator("#crmChatAlerts")).toContainText(
        "Please check this lead."
    );
    await expect(page.getByRole("link", { name: "Open chat" })).toHaveAttribute(
        "href",
        "/admin/chat?conversation=7"
    );
    await page.evaluate(() =>
        window.testChatReceive({
            kind: "message",
            message_id: 10,
            conversation_id: 7,
            sender_id: 2,
            sender_name: "Sales User",
            preview: "Duplicate",
        })
    );
    await page.evaluate(() =>
        window.testChatReceive({
            kind: "message",
            message_id: 11,
            conversation_id: 7,
            sender_id: 1,
            sender_name: "Me",
            preview: "Own message",
        })
    );
    await expect(page.locator("#crmChatAlerts .toast")).toHaveCount(1);
});
