document.addEventListener("DOMContentLoaded", () => {
    const textarea = document.querySelector(".bio-textarea");
    const counter = document.getElementById("bio-count");
    const max = parseInt(textarea.getAttribute("maxlength"));

    function updateCount() {
        const length = textarea.value.length;
        counter.textContent = length;

        if (length >= max) {
            counter.parentElement.classList.add("too-long");
        } else {
            counter.parentElement.classList.remove("too-long");
        }
    }

    textarea.addEventListener("input", updateCount);
    updateCount();
});

