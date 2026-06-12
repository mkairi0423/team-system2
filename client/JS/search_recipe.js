// 💡 前回の検索キーワードを記憶する変数（APIの重複リクエストを防ぐ）
window.lastKeyword = "";

// 💡 仮のログインユーザーID（環境に合わせて変更してください）
const currentUserId = 1; 

// 💡 検索時に取得した「全在庫」を一時的にキープしておくためのグローバル変数
let loadedAllStocks = []; 

/**
 * 1. 検索ボタンをクリックしたときのイベント
 */
const searchBtn = document.getElementById('recipeSearchBtn');
if (searchBtn) {
    searchBtn.addEventListener('click', async () => {
        console.log("検索ボタンが押されました");

        const keywordInput = document.getElementById('recipeSearchInput');
        const keyword = keywordInput.value.trim();
        
        // 💡 重複リクエストガード：前回と同じキーワードなら処理を中断
        if (window.lastKeyword === keyword && !searchBtn.disabled) {
            console.log("前回と同じキーワードなので検索をスキップします");
            return;
        }

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
                // 💡 成功時のみキーワードを記録
                window.lastKeyword = keyword;
                
                resultTitle.textContent = `🍳 「${keyword}」の必要食材確認`;
                loadedAllStocks = result.all_stocks || []; 
                
                renderCheckboxes(result.ingredients);
            } else {
                alert('エラー: ' + result.message);
                resultSection.style.display = 'none';
            }
        } catch (error) {
            console.error('通信エラー:', error);
            alert('サーバーとの通信に失敗しました。コンソールを確認してください。');
        } finally {
            // 💡 通信が終わったらボタンを復活
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
    container.innerHTML = ''; 

    const fridgeItems = ingredients.filter(item => item.in_stock && item.location === 'fridge');
    const freezerItems = ingredients.filter(item => item.in_stock && item.location === 'freezer');
    const outOfStockItems = ingredients.filter(item => !item.in_stock);

    if (fridgeItems.length > 0) {
        const titleFridge = document.createElement('h4');
        titleFridge.className = 'group-title title-fridge';
        titleFridge.style.color = '#2ecc71';
        titleFridge.innerHTML = '✨ 冷蔵庫にある食材（すぐ使えます）';
        container.appendChild(titleFridge);
        fridgeItems.forEach(item => container.appendChild(createIngredientRow(item, true, '冷蔵庫')));
    }

    if (fridgeItems.length > 0 && (freezerItems.length > 0 || outOfStockItems.length > 0)) {
        container.appendChild(document.createElement('hr'));
    }

    if (freezerItems.length > 0) {
        const titleFreezer = document.createElement('h4');
        titleFreezer.className = 'group-title title-freezer';
        titleFreezer.style.color = '#3498db';
        titleFreezer.innerHTML = '❄️ 冷凍庫にある食材（解凍して使えます）';
        container.appendChild(titleFreezer);
        freezerItems.forEach(item => container.appendChild(createIngredientRow(item, true, '冷凍庫')));
    }

    if (freezerItems.length > 0 && outOfStockItems.length > 0) {
        container.appendChild(document.createElement('hr'));
    }

    if (outOfStockItems.length > 0) {
        const titleOut = document.createElement('h4');
        titleOut.className = 'group-title title-out';
        titleOut.style.color = '#e67e22';
        titleOut.innerHTML = '🛒 買い足しが必要な食材（買い物リスト）';
        container.appendChild(titleOut);
        outOfStockItems.forEach(item => container.appendChild(createIngredientRow(item, false, '在庫なし')));
    }
}

/**
 * 3. 食材の1行分のHTML要素を生成する関数
 */
function createIngredientRow(item, isAvailable, statusLabel) {
    const div = document.createElement('div');
    div.className = `checkbox-item ${isAvailable ? 'item-available' : 'item-missing'}`;
    div.style.padding = '8px 0';
    div.style.borderBottom = '1px solid #eee';

    let badgeColor = statusLabel === '冷蔵庫' ? 'status-in' : (statusLabel === '冷凍庫' ? 'status-freezer' : 'status-out');

    div.innerHTML = `
        <label style="display: flex; align-items: center; justify-content: space-between; width: 100%; cursor: pointer;">
            <span>
                <input type="checkbox" name="food_items" value="${item.id || ''}" data-name="${item.food}" checked>
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
 * 4. 調理開始ボタン
 */
const startBtn = document.getElementById('startCookingBtn');
if (startBtn) {
    startBtn.addEventListener('click', () => {
        alert("調理を開始します！画面4へ遷移する処理をここに記述します。");
    });
}

/**
 * 5. 「他の食材を追加」ボタンとモーダル制御
 */
const manualAddBtn = document.getElementById('manualAddBtn');
const stockModal = document.getElementById('stockModal');
const closeModalBtn = document.getElementById('closeModalBtn');
const modalSubmitBtn = document.getElementById('modalSubmitBtn');

if (manualAddBtn && stockModal) {
    manualAddBtn.addEventListener('click', () => {
        const allStockContainer = document.getElementById('allStockContainer');
        allStockContainer.innerHTML = '';
        stockModal.style.display = 'block';

        const existingNames = Array.from(document.querySelectorAll('input[name="food_items"]'))
                                   .map(input => input.getAttribute('data-name'));

        loadedAllStocks.forEach(stock => {
            if (existingNames.includes(stock.food)) return;

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
    });

    closeModalBtn.addEventListener('click', () => stockModal.style.display = 'none');
    window.addEventListener('click', (e) => { if (e.target === stockModal) stockModal.style.display = 'none'; });

    modalSubmitBtn.addEventListener('click', () => {
        const selectedModalItems = document.querySelectorAll('input[name="modal_food_items"]:checked');
        const mainContainer = document.getElementById('checkboxContainer');

        selectedModalItems.forEach(cb => {
            const location = cb.getAttribute('data-location');
            const item = { id: cb.value, food: cb.getAttribute('data-name'), location: location, in_stock: true };
            const isFreezer = (location === 'freezer');
            const targetTitleClass = isFreezer ? '.title-freezer' : '.title-fridge';
            const newRow = createIngredientRow(item, true, isFreezer ? '冷凍庫' : '冷蔵庫');
            
            let targetTitle = mainContainer.querySelector(targetTitleClass);
            if (!targetTitle) {
                targetTitle = document.createElement('h4');
                targetTitle.className = `group-title ${targetTitleClass.replace('.', '')}`;
                targetTitle.style.color = isFreezer ? '#3498db' : '#2ecc71';
                targetTitle.innerHTML = isFreezer ? '❄️ 冷凍庫にある食材（解凍して使えます）' : '✨ 冷蔵庫にある食材（すぐ使えます）';
                mainContainer.prepend(targetTitle);
            }
            targetTitle.after(newRow);
        });
        stockModal.style.display = 'none';
    });
}