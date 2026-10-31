import { copyFileSync } from "node:fs";

for (const [source, destination] of [
    ["pusher-js/dist/web/pusher.min.js", "pusher.min.js"],
    [
        "pusher-js/dist/web/pusher.min.js.LICENSE.txt",
        "pusher.min.js.LICENSE.txt",
    ],
    ["laravel-echo/dist/echo.iife.js", "echo.iife.js"],
    ["laravel-echo/LICENSE.md", "echo.LICENSE"],
]) {
    copyFileSync(
        new URL("../node_modules/" + source, import.meta.url),
        new URL("../public/admin/libraries/js/" + destination, import.meta.url)
    );
}
