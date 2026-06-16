// ==========================================
// js/mode_switch.js （null安全ガード付き修正版）
// ==========================================

const toggle = document.getElementById('darkModeToggle');

// 1. 過去にダークモードが選択されていた場合の復元処理
if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark-mode');

    // 💡 画面にトグルボタンが存在するときだけ、チェックをONにする（nullガード）
    if (toggle) {
        toggle.checked = true;
    }
}

// 2. トグルボタンがクリックされた時の切り替えイベント登録
// 💡 「toggle が存在する場合のみ」イベントリスナーを設定する（nullガード）
if (toggle) {
    toggle.addEventListener('change', () => {
        if (toggle.checked) {
            document.body.classList.add('dark-mode');
            localStorage.setItem('theme', 'dark');
        } else {
            document.body.classList.remove('dark-mode');
            localStorage.setItem('theme', 'light');
        }
    });
}