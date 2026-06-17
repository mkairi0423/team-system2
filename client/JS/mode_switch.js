const toggle = document.getElementById('darkModeToggle');

// 保存済みテーマを読み込み
if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark-mode');
    toggle.checked = true;
}

toggle.addEventListener('change', () => {
    if (toggle.checked) {
        document.body.classList.add('dark-mode');
        localStorage.setItem('theme', 'dark');
    } else {
        document.body.classList.remove('dark-mode');
        localStorage.setItem('theme', 'light');
    }
});

const darkToggle = document.getElementById("darkModeToggle");

if (localStorage.getItem("theme") === "dark") {
    document.body.classList.add("dark-mode");
    darkToggle.checked = true;
}

darkToggle.addEventListener("change", () => {
    if (darkToggle.checked) {
        document.body.classList.add("dark-mode");
        localStorage.setItem("theme", "dark");
    } else {
        document.body.classList.remove("dark-mode");
        localStorage.setItem("theme", "light");
    }
});