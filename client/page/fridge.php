<?php
// fridge.php
$title = "冷蔵庫管理";
$page = "fridge";

// バックエンドのロジックを読み込み
require_once __DIR__ . "/../../server/page/fridge_server.php";

include("template/header.php");
include("template/sidebar.php");
?>

<!-- fridge専用のCSSを読み込み -->
<link rel="stylesheet" href="../css/fridge.css">

<div class="main">
    <div class="topbar">
        <h1>冷蔵庫管理</h1>
    </div>

    <!-- 保管場所絞り込みタブ -->
    <div class="filter-container">
        <button class="filter-tab active" data-loc-id="all" onclick="filterLocation('all')">
            <span class="tab-icon">📦</span> すべて
        </button>
        <?php foreach ($locations as $loc): ?>
            <button class="filter-tab" data-loc-id="<?= $loc['location_id'] ?>"
                onclick="filterLocation(<?= $loc['location_id'] ?>)">
                <?= htmlspecialchars($loc['location_name'], ENT_QUOTES, 'UTF-8') ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- 表示の自由切り替えコントロールパネル -->
    <div class="display-control-panel">

        <!-- 並び替え（ソート） -->
        <div class="control-group">
            <label class="control-label">並び替え</label>
            <div class="select-wrapper">
                <select id="sortSelect" class="control-select" onchange="changeSort()">
                    <option value="expiry_asc">⌛ 期限が近い順</option>
                    <option value="created_desc">✨ 登録が新しい順</option>
                    <option value="name_asc">🔤 名前順 (50音順)</option>
                </select>
            </div>
        </div>
    </div>

    <!-- 検索ボックス -->
    <div class="search-panel">
        <div class="search-input-wrapper">
            <span class="search-icon">🔍</span>
            <input type="text" id="foodSearch" placeholder="食材名でリアルタイム検索..." class="search-box" oninput="searchFoods()">
        </div>
    </div>

    <!-- 現在選択中の場所を示すステータスバー -->
    <div class="current-view-status" id="currentViewStatus"></div>

    <!-- 動的に中身が書き換わる食材リストコンテナ -->
    <div id="ingredientsContainer"></div>

<!-- 自由表示を制御するJavaScript -->
<script>
    // PHPからデータをJavaScriptに安全に渡す
    const dbIngredients = <?= json_encode(array_values($ingredients), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?> || [];
    const dbCategories = <?= json_encode(array_values($categories), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?> || [];
    const dbLocations = <?= json_encode(array_values($locations), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?> || [];

    // 現在の表示状態（ステート）
    let currentFilterLocation = 'all';
    let currentGrouping = 'category';
    let currentSort = 'expiry_asc';
    let currentSearch = '';

    document.addEventListener('DOMContentLoaded', () => {
        renderIngredients();
    });

    function filterLocation(locationId) {
        currentFilterLocation = locationId;
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.classList.toggle('active', tab.getAttribute('data-loc-id') == locationId);
        });
        renderIngredients();
    }

    function changeGrouping(groupType) {
        currentGrouping = groupType;
        document.querySelectorAll('.toggle-btn').forEach(btn => {
            btn.classList.toggle('active', btn.id === `group-${groupType}`);
        });
        renderIngredients();
    }

    function changeSort() {
        currentSort = document.getElementById('sortSelect').value;
        renderIngredients();
    }

    function searchFoods() {
        currentSearch = document.getElementById('foodSearch').value.toLowerCase();
        renderIngredients();
    }

    // 食材を描画するメイン関数
    function renderIngredients() {
        const container = document.getElementById('ingredientsContainer');
        container.innerHTML = '';

        // 1. フィルタリング処理（検索語 & 保管場所絞り込み）
        let list = dbIngredients.filter(ing => {
            const matchesLocation = (currentFilterLocation === 'all' || ing.storage_location_id == currentFilterLocation);
            const matchesSearch = ing.food_name.toLowerCase().includes(currentSearch);
            return matchesLocation && matchesSearch;
        });

        // 2. ステータスバーを動的に更新
        updateStatusBar(list.length);

        // 3. 並び替え（ソート）処理
        list.sort((a, b) => {
            if (currentSort === 'expiry_asc') {
                if (!a.expiration_date) return 1;
                if (!b.expiration_date) return -1;
                return new Date(a.expiration_date) - new Date(b.expiration_date);
            } else if (currentSort === 'created_desc') {
                return b.ingredient_id - a.ingredient_id;
            } else if (currentSort === 'name_asc') {
                return a.food_name.localeCompare(b.food_name, 'ja');
            }
            return 0;
        });

        // 4. グループ化（レンダリング）処理
        if (currentGrouping === 'none') {
            if (list.length === 0) {
                container.innerHTML = '<div class="no-data"><span class="no-data-icon">🥬</span><p>該当する食材がありません</p></div>';
                return;
            }
            const listWrapper = document.createElement('div');
            listWrapper.className = 'flat-list-panel';
            list.forEach(ing => listWrapper.appendChild(createFoodRowElement(ing)));
            container.appendChild(listWrapper);

        } else {
            const groups = currentGrouping === 'category' ? dbCategories : dbLocations;
            const groupKey = currentGrouping === 'category' ? 'category_id' : 'storage_location_id';
            const groupNameKey = currentGrouping === 'category' ? 'category_name' : 'location_name';

            let hasAnyVisibleGroup = false;

            groups.forEach(group => {
                const groupItems = list.filter(ing => ing[groupKey] == group[groupKey]);
                if (groupItems.length === 0) return;

                hasAnyVisibleGroup = true;

                const details = document.createElement('details');
                details.className = 'category-details';
                details.open = true;

                const summary = document.createElement('summary');
                summary.innerHTML = `
                    <div class="summary-content">
                        <span class="category-title">${escapeHtml(group[groupNameKey])}</span>
                        <span class="category-count">${groupItems.length}件</span>
                    </div>
                `;
                details.appendChild(summary);

                const contentDiv = document.createElement('div');
                contentDiv.className = 'category-content';
                groupItems.forEach(ing => {
                    contentDiv.appendChild(createFoodRowElement(ing));
                });

                details.appendChild(contentDiv);
                container.appendChild(details);
            });

            if (!hasAnyVisibleGroup) {
                container.innerHTML = '<div class="no-data"><span class="no-data-icon">🥬</span><p>該当する食材がありません</p></div>';
            }
        }
    }

    // ステータスバーの表示切り替えロジック
    function updateStatusBar(count) {
        const statusBar = document.getElementById('currentViewStatus');
        if (!statusBar) return;

        let locationName = 'すべての場所';

        if (currentFilterLocation !== 'all') {
            const selectedLoc = dbLocations.find(loc => loc.location_id == currentFilterLocation);
            if (selectedLoc) {
                locationName = selectedLoc.location_name;
            }
        }

        let html = `
        <div class="status-badge-container">
            <div class="status-badge-info">
                <span class="status-badge-title">${escapeHtml(locationName)}</span>
                <span class="status-badge-desc">を表示中（全 <strong class="highlight-count">${count}</strong> 件）</span>
            </div>
        </div>
        `;

        if (currentSearch) {
            html += `
            <div class="search-keyword-badge">
                🔍 「${escapeHtml(currentSearch)}」
            </div>
            `;
        }

        statusBar.innerHTML = html;
    }

    // 食材1行分のDOM要素を組み立てる
    function createFoodRowElement(ing) {
        const row = document.createElement('div');
        row.className = 'food-row';
        row.setAttribute('data-id', ing.ingredient_id);

        // 賞味期限バッジ＆カラー判定
        let dateBadgeHtml = '';
        if (ing.expiration_date) {
            const today = new Date();
            today.setHours(0,0,0,0);
            const expDate = new Date(ing.expiration_date);
            expDate.setHours(0,0,0,0);
            
            const diffTime = expDate - today;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            let statusClass = 'date-normal';
            let labelPrefix = '';

            if (diffDays < 0) {
                statusClass = 'date-expired';
                labelPrefix = '期限切れ ';
            } else if (diffDays <= 2) {
                statusClass = 'date-warning';
                labelPrefix = 'まもなく ';
            }

            dateBadgeHtml = `<span class="badge badge-date ${statusClass}">${escapeHtml(ing.term_type)}: ${escapeHtml(ing.expiration_date)} (${labelPrefix}${diffDays < 0 ? Math.abs(diffDays) + '日前' : diffDays === 0 ? '今日' : 'あと' + diffDays + '日'})</span>`;
        }

        let optionsHtml = `<option value="" disabled selected>移動...</option>`;
        dbLocations.forEach(loc => {
            const isDisabled = ing.storage_location_id == loc.location_id ? 'disabled' : '';
            optionsHtml += `<option value="${loc.location_id}" ${isDisabled}>${escapeHtml(loc.location_name)} へ</option>`;
        });

        const showLocBadge = currentFilterLocation === 'all'
            ? `<span class="badge badge-loc">${escapeHtml(ing.location_name)}</span>`
            : '';

        row.innerHTML = `
        <div class="food-info">
            <div class="food-main-line">
                <span class="food-name">${escapeHtml(ing.food_name)}</span>
                <span class="food-qty">${escapeHtml(ing.quantity)}${escapeHtml(ing.unit)}</span>
            </div>
            <div class="badges-wrapper">
                ${showLocBadge}
                <span class="badge badge-cat">${escapeHtml(ing.category_name)}</span>
                ${dateBadgeHtml}
            </div>
        </div>
        <div class="food-actions">
            <select class="move-select" onchange="moveLocation(${ing.ingredient_id}, this.value)">
                ${optionsHtml}
            </select>
            <button class="consume-btn" onclick="deleteFood(${ing.ingredient_id}, '消費済')">消費</button>
            <button class="delete-btn" onclick="deleteFood(${ing.ingredient_id}, '廃棄')">廃棄</button>
        </div>
        `;
        return row;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // 移動API呼び出し
    function moveLocation(ingredientId, targetLocationId) {
        if (!targetLocationId) return;
        if (!confirm('保管場所を移動しますか？')) {
            renderIngredients();
            return;
        }

        fetch('../../server/page/fridge_server.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `ingredient_id=${ingredientId}&location_id=${targetLocationId}`
        })
        .then(res => {
            if (!res.ok) throw new Error('HTTPエラーが発生しました。');
            return res.text();
        })
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    const ing = dbIngredients.find(item => item.ingredient_id == ingredientId);
                    const targetLoc = dbLocations.find(loc => loc.location_id == targetLocationId);
                    
                    if (ing && targetLoc) {
                        ing.storage_location_id = targetLocationId;
                        ing.location_name = targetLoc.location_name;
                    }
                    renderIngredients();
                } else {
                    alert('移動に失敗しました: ' + data.error);
                    renderIngredients();
                }
            } catch (e) {
                alert('サーバーから不正なレスポンスがありました:\n' + text);
            }
        })
        .catch(err => {
            console.error(err);
            alert('通信エラーが発生しました。');
        });
    }

    // 食材の「消費」または「廃棄」
    function deleteFood(ingredientId, actionStatus) {
        const message = actionStatus === '消費済' ? 'この食材を消費しましたか？' : 'この食材を廃棄しますか？';
        if (!confirm(message)) return;

        fetch('../../server/page/fridge_server.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `ingredient_id=${ingredientId}&status=${actionStatus}`
        })
        .then(res => {
            if (!res.ok) throw new Error('HTTPエラーが発生しました。');
            return res.text();
        })
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    const index = dbIngredients.findIndex(ing => ing.ingredient_id == ingredientId);
                    if (index !== -1) {
                        dbIngredients.splice(index, 1);
                    }
                    renderIngredients();
                } else {
                    alert('処理に失敗しました: ' + data.error);
                }
            } catch (e) {
                alert('サーバーから不正なレスポンスがありました:\n' + text);
            }
        })
        .catch(err => {
            console.error(err);
            alert('通信エラーが発生しました。');
        });
    }
</script>

<?php include("template/footer.php"); ?>