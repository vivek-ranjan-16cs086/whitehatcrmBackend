(() => {
    const form = document.getElementById("adminProfilePictureForm");
    if (!form) return;
    const fileInput = document.getElementById("adminProfilePictureFile");
    const preview = document.getElementById("adminProfilePreview");
    const error = document.getElementById("adminProfilePictureError");
    const save = document.getElementById("adminProfilePictureSave");
    let previewUrl;
    function showError(message) {
        error.textContent = message;
        error.hidden = !message;
    }
    function image(container, url) {
        const img = document.createElement("img");
        img.className = "chat-avatar";
        img.src = url;
        img.alt = "Your profile picture";
        container.replaceChildren(img);
    }
    fileInput.addEventListener("change", () => {
        showError("");
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = null;
        }
        const file = fileInput.files[0];
        if (!file) return;
        if (file.size > 2097152) {
            showError("Choose a picture smaller than 2 MB.");
            fileInput.value = "";
            return;
        }
        if (
            !["image/jpeg", "image/png", "image/gif", "image/webp"].includes(
                file.type
            )
        ) {
            showError("Choose a JPG, PNG, GIF or WebP picture.");
            fileInput.value = "";
            return;
        }
        previewUrl = URL.createObjectURL(file);
        image(preview, previewUrl);
    });
    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        const file = fileInput.files[0];
        if (!file) return;
        save.disabled = true;
        showError("");
        const body = new FormData();
        body.append("profile_pic", file);
        try {
            const response = await fetch(form.dataset.url, {
                method: "POST",
                credentials: "same-origin",
                body,
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                },
            });
            const data = await response.json();
            if (!response.ok)
                throw new Error(
                    Object.values(data.errors || {}).flat()[0] ||
                        data.message ||
                        "Unable to save your picture."
                );
            document
                .querySelectorAll(
                    ".user-avatar,.user-dropdown-avatar,#chatProfileButton,#adminProfilePreview"
                )
                .forEach((container) =>
                    image(container, data.profile_picture_url)
                );
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = null;
            }
            fileInput.value = "";
            bootstrap.Modal.getInstance(
                document.getElementById("adminProfilePictureModal")
            )?.hide();
        } catch (failure) {
            showError(failure.message);
        } finally {
            save.disabled = false;
        }
    });
})();
