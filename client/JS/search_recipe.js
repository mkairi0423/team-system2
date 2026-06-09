// 💡 仮のログインユーザーID（環境に合わせて変更してください）
const currentUserId = 1; 

// 💡 検索時に取得した「全在庫」を一時的にキープしておくためのグローバル変数
let loadedAllStocks = []; 

/**
 * 1. 検索ボタンをクリックしたときのイベント（連打防止ガード付き）
 */
const searchBtn = document.getElementById('recipeSearchBtn');
if (searchBtn) {
    searchBtn.addEventListener('click', async () => {
        console.log("検索ボタンが押されました");

        // 💡 連打防止ガード：すでに通信中なら処理を無視
        if (searchBtn.disabled) return;

        const keywordInput = document.getElementById('recipeSearchInput');
        const keyword = keywordInput.value.trim();
        
        if (!keyword) {
            alert('作りたい料理名を入力してください！');
            return;
        }

        const resultSection = document.getElementById('resultSection');
        const resultTitle = document.getElementById('resultTitle');
        const container = document.getElementById('checkboxContainer');
        
        // 💡 通信開始時にボタンを無効化（429エラーやサーバー過負荷を防ぐ）
        searchBtn.disabled = true;
        searchBtn.style.opacity = '0.6';
        searchBtn.style.cursor = 'not-allowed';

        // 状態のリセット
        resultTitle.textContent = `🔄 「${keyword}」の必要食材をAIが計算中...`;
        resultSection.style.display = 'block';
        container.innerHTML = '読み込み中...';

        try {
            const apiUrl = `../../server/page/get_recipe_ingredients.php?user_id=${currentUserId}&keyword=${encodeURIComponent(keyword)}`;
            console.log("通信開始:", apiUrl);

            const response = await fetch(apiUrl);
            const result = await response.json();
            console.log("サーバーからの返答:", result);

            if (result.success) {
                resultTitle.textContent = `🍳 「${keyword}」の必要食材確認`;
                
                // PHP側から返ってきたユーザーの全在庫（locationカラム入り）をキープ
                loadedAllStocks = result.all_stocks || []; 
                
                // 3分割関数を呼び出す
                renderCheckboxes(result.ingredients);
            } else {
                alert('エラー: ' + result.message);
                resultSection.style.display = 'none';
            }
        } catch (error) {
            console.error('通信エラー:', error);
            alert('サーバーとの通信に失敗しました。コンソールを確認してください。');
        } finally {
            // 💡 成功・失敗に関わらず、通信が終わったらボタンを絶対に復活させる
            searchBtn.disabled = false;
            searchBtn.style.opacity = '1';
            searchBtn.style.cursor = 'pointer';
        }
    });
} else {
    console.error("エラー: recipeSearchBtn が見つかりません。HTMLのIDを確認してください。");
}

/**
 * 2. AIが抽出した食材を「冷蔵庫」「冷凍庫」「在庫なし」の3つに分けて表示する関数
 */
function renderCheckboxes(ingredients) {
    const container = document.getElementById('checkboxContainer');
    container.innerHTML = ''; // 一旦画面をクリア

    // 💡 locationカラムの値を元に、3つのグループに仕分ける
    const fridgeItems = ingredients.filter(item => item.in_stock && item.location === 'fridge');
    const freezerItems = ingredients.filter(item => item.in_stock && item.location === 'freezer');
    const outOfStockItems = ingredients.filter(item => !item.in_stock);

    // --- 🟢 冷蔵庫グループ ---
    if (fridgeItems.length > 0) {
        const titleFridge = document.createElement('h4');
        titleFridge.className = 'group-title title-fridge';
        titleFridge.style.color = '#2ecc71'; // 緑色
        titleFridge.innerHTML = '✨ 冷蔵庫にある食材（すぐ使えます）';
        container.appendChild(titleFridge);

        fridgeItems.forEach(item => {
            container.appendChild(createIngredientRow(item, true, '冷蔵庫'));
        });
    }

    // 分割線1
    if (fridgeItems.length > 0 && (freezerItems.length > 0 || outOfStockItems.length > 0)) {
        container.appendChild(document.createElement('hr'));
    }

    // --- 🔵 冷凍庫グループ（新設） ---
    if (freezerItems.length > 0) {
        const titleFreezer = document.createElement('h4');
        titleFreezer.className = 'group-title title-freezer';
        titleFreezer.style.color = '#3498db'; // 青色（氷のイメージ）
        titleFreezer.innerHTML = '❄️ 冷凍庫にある食材（解凍して使えます）';
        container.appendChild(titleFreezer);

        freezerItems.forEach(item => {
            container.appendChild(createIngredientRow(item, true, '冷凍庫'));
        });
    }

    // 分割線2
    if (freezerItems.length > 0 && outOfStockItems.length > 0) {
        container.appendChild(document.createElement('hr'));
    }

    // --- 🟠 在庫なしグループ ---
    if (outOfStockItems.length > 0) {
        const titleOut = document.createElement('h4');
        titleOut.className = 'group-title title-out';
        titleOut.style.color = '#e67e22'; // オレンジ
        titleOut.innerHTML = '🛒 買い足しが必要な食材（買い物リスト）';
        container.appendChild(titleOut);

        outOfStockItems.forEach(item => {
            container.appendChild(createIngredientRow(item, false, '在庫なし'));
        });
    }
}

/**
 * 3. 食材の1行分のHTML要素を生成する関数（保管場所ラベル付き）
 */
function createIngredientRow(item, isAvailable, statusLabel) {
    const div = document.createElement('div');
    div.className = `checkbox-item ${isAvailable ? 'item-available' : 'item-missing'}`;
    div.style.padding = '8px 0';
    div.style.borderBottom = '1px solid #eee';

    const isChecked = 'checked'; 
    
    // バッジのCSSクラスの割り当て
    let badgeColor = 'status-out';
    if (statusLabel === '冷蔵庫') badgeColor = 'status-in';
    if (statusLabel === '冷凍庫') badgeColor = 'status-freezer'; 

    div.innerHTML = `
        <label style="display: flex; align-items: center; justify-content: space-between; width: 100%; cursor: pointer;">
            <span>
                <input type="checkbox" name="food_items" value="${item.id || ''}" data-name="${item.food}" ${isChecked}>
                <span class="food-name">${item.food}</span>
            </span>
            <span class="stock-status ${badgeColor}" style="font-size: 0.8em; opacity: 0.7; padding: 2px 6px; border-radius: 4px;">
                ${statusLabel === '在庫なし' ? '❌ 在庫なし' : statusLabel}
            </span>
        </label>
    `;
    return div;
}

/**
 * 4. 調理開始ボタン（画面4への遷移）
 */
const startBtn = document.getElementById('startCookingBtn');
if (startBtn) {
    startBtn.addEventListener('click', () => {
        alert("調理を開始します！画面4へ遷移する処理をここに記述します。");
    });
}

/**
 * 5. 「他の食材を追加」ボタンを押した時のモーダル制御（冷凍・冷蔵のバッジ表示付き）
 */
const manualAddBtn = document.getElementById('manualAddBtn');
const stockModal = document.getElementById('stockModal');
const closeModalBtn = document.getElementById('closeModalBtn');
const modalSubmitBtn = document.getElementById('modalSubmitBtn');

if (manualAddBtn && stockModal) {
    // ボタンクリックで保存してある在庫からモーダルを開く
    manualAddBtn.addEventListener('click', () => {
        const allStockContainer = document.getElementById('allStockContainer');
        allStockContainer.innerHTML = ''; // クリア
        stockModal.style.display = 'block'; // モーダルを表示

        // 現在すでにメイン画面に並んでいる食材の「名前」を取得して重複を防ぐ
        const existingNames = Array.from(document.querySelectorAll('input[name="food_items"]'))
                                   .map(input => input.getAttribute('data-name'));

        // キープしておいた全在庫をループで回してチェックボックスを作る
        loadedAllStocks.forEach(stock => {
            if (existingNames.includes(stock.food)) return;

            // 💡 モーダル内でも冷蔵庫か冷凍庫かわかるように判別
            const isFreezer = stock.location === 'freezer';
            const locationBadge = isFreezer ? '❄️ 冷凍' : '✨ 冷蔵';
            const badgeStyle = isFreezer ? 'color: #3498db;' : 'color: #2ecc71;';

            const div = document.createElement('div');
            div.className = 'modal-stock-item';
            div.style.padding = '10px';
            div.style.borderBottom = '1px solid #eee';
            div.innerHTML = `
                <label style="display: flex; align-items: center; justify-content: space-between; cursor: pointer; width: 100%;">
                    <span>
                        <input type="checkbox" name="modal_food_items" value="${stock.id}" data-name="${stock.food}" data-location="${stock.location}" style="margin-right: 10px;">
                        <span>${stock.food}</span>
                    </span>
                    <span style="font-size: 0.8em; ${badgeStyle}">${locationBadge}</span>
                </label>
            `;
            allStockContainer.appendChild(div);
        });

        if (allStockContainer.innerHTML === '') {
            allStockContainer.innerHTML = '<p style="color: #888; padding: 10px;">追加できる他の在庫がありません。</p>';
        }
    });

    // × ボタンでモーダルを閉じる
    closeModalBtn.addEventListener('click', () => {
        stockModal.style.display = 'none';
    });

    // モーダルの外側をクリックしても閉じるようにする
    window.addEventListener('click', (e) => {
        if (e.target === stockModal) {
            stockModal.style.display = 'none';
        }
    });

    // 【確定】選択した食材を適切なメインリスト（冷蔵庫 or 冷凍庫）に動的追加する処理
    modalSubmitBtn.addEventListener('click', () => {
        const selectedModalItems = document.querySelectorAll('input[name="modal_food_items"]:checked');
        const mainContainer = document.getElementById('checkboxContainer');

        if (selectedModalItems.length === 0) {
            stockModal.style.display = 'none';
            return;
        }

        selectedModalItems.forEach(cb => {
            const location = cb.getAttribute('data-location'); // 'fridge' または 'freezer'
            const item = {
                id: cb.value,
                food: cb.getAttribute('data-name'),
                location: location,
                in_stock: true
            };

            const isFreezer = (location === 'freezer');
            const targetTitleClass = isFreezer ? '.title-freezer' : '.title-fridge';
            const statusLabel = isFreezer ? '冷凍庫' : '冷蔵庫';
            const titleColor = isFreezer ? '#3498db' : '#2ecc71';
            const titleHtml = isFreezer ? '❄️ 冷凍庫にある食材（解凍して使えます）' : '✨ 冷蔵庫にある食材（すぐ使えます）';

            const newRow = createIngredientRow(item, true, statusLabel);
            
            // 💡 動的見出し生成：追加先のグループ見出しがメイン画面にまだ無ければその場で作る
            let targetTitle = mainContainer.querySelector(targetTitleClass);
            if (!targetTitle) {
                targetTitle = document.createElement('h4');
                targetTitle.className = `group-title ${targetTitleClass.replace('.', '')}`;
                targetTitle.style.color = titleColor;
                targetTitle.innerHTML = titleHtml;
                
                if (isFreezer) {
                    // 冷凍庫の見出しを新しく作る場合、冷蔵庫グループがあればその後ろ、無ければ先頭に置く
                    const fridgeTitle = mainContainer.querySelector('.title-fridge');
                    if (fridgeTitle) {
                        // 冷蔵庫グループの一番ケツ（hrの手前）を探して挿入
                        const hr = mainContainer.querySelector('hr');
                        if (hr) hr.before(targetTitle);
                        else fridgeTitle.after(targetTitle);
                    } else {
                        mainContainer.prepend(targetTitle);
                    }
                } else {
                    // 冷蔵庫の見出しを新しく作る場合は常に最先頭
                    mainContainer.prepend(targetTitle);
                }
            }
            
            // 対象の見出しの直後に食材を追加
            targetTitle.after(newRow);
        });

        // モーダルを閉じる
        stockModal.style.display = 'none';
    });
}