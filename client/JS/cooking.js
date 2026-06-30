document.addEventListener("DOMContentLoaded", () => {
    // 検索画面の全食材コンテナを取得
    const container = document.querySelector('form'); // もしくはリストを囲む親要素

    container.addEventListener("click", (e) => {
        // ボタンが押されたか判定
        if (!e.target.classList.contains("qty-btn")) return;

        const button = e.target;
        const type = button.dataset.type; // "max", "half", "third"
        
        // ★重要: クリックしたボタンの親（行）を特定し、その中の入力欄を探す
        const row = button.closest('div'); // 各食材の行を探す
        const input = row.querySelector('input[type="number"]');
        const maxVal = parseFloat(button.dataset.max); // 在庫の最大値

        if (!input) return;

        // 計算ロジック
        if (type === "max") {
            input.value = maxVal;
        } else if (type === "half") {
            input.value = maxVal / 2;
        } else if (type === "third") {
            input.value = (maxVal / 3).toFixed(1); // 少数第1位まで
        }
    });
});