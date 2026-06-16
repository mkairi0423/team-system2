document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('darkModeToggle');

    // 現在のテーマを反映（全ページ）
    const applyTheme = (theme) => {
        if (theme === 'dark') {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
        }
    };

    // 保存テーマを読み込み（全ページ適用）
    const savedTheme = localStorage.getItem('theme');
    applyTheme(savedTheme);

    // スイッチがあるページだけ操作を許可
    if (toggle) {
        toggle.checked = savedTheme === 'dark';

        toggle.addEventListener('change', () => {
            const theme = toggle.checked ? 'dark' : 'light';
            localStorage.setItem('theme', theme);
            applyTheme(theme);
        });
    }
});