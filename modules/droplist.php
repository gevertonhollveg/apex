<?php
/**
 * Droplist module
 * Browses XML bags from modules/bags and displays drop information.
 */

echo '<div class="page-title"><span>' . lang('module_titles_txt_30', true) . '</span></div>';

$bagsDir = __PATH_MODULES__ . 'bags/';
$bagOptions = array();

if (is_dir($bagsDir)) {
    $files = scandir($bagsDir);
    foreach ($files as $fileName) {
        if ($fileName === '.' || $fileName === '..') continue;
        if (strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) !== 'xml') continue;

        $fullPath = $bagsDir . $fileName;
        if (!is_file($fullPath)) continue;

        $displayName = pathinfo($fileName, PATHINFO_FILENAME);

        libxml_use_internal_errors(true);
        $sx = @simplexml_load_file($fullPath, 'SimpleXMLElement', LIBXML_NONET);
        if ($sx) {
            if (isset($sx->BagConfig)) {
                $attr = $sx->BagConfig->attributes();
                if (isset($attr['Name']) && check_value((string)$attr['Name'])) {
                    $displayName = (string)$attr['Name'];
                }
            }
        }

        $bagOptions[] = array(
            'value' => $fileName,
            'text' => $displayName,
        );
    }
}

usort($bagOptions, function($a, $b) {
    return strnatcasecmp($a['text'], $b['text']);
});

$bagOptionsJson = json_encode($bagOptions);
?>

<div class="droplist-module">
    <div class="droplist-toolbar">
        <div class="droplist-filter-row">
            <div class="droplist-select-wrap">
                <label for="droplistBag" class="droplist-label">Bag XML</label>
                <select id="droplistBag" class="form-control droplist-control">
                    <option value="">Selecione uma bag...</option>
                </select>
            </div>
            <div class="droplist-search-wrap">
                <label for="droplistSearch" class="droplist-label">Buscar</label>
                <input id="droplistSearch" class="form-control droplist-control" type="text" placeholder="Buscar por nome do item...">
            </div>
            <div class="droplist-type-wrap">
                <label for="droplistType" class="droplist-label">Type</label>
                <select id="droplistType" class="form-control droplist-control">
                    <option value="">All</option>
                    <option value="item" selected>Item</option>
                    <option value="ruud">Ruud</option>
                    <option value="coin">Coin</option>
                    <option value="zen">Zen</option>
                </select>
            </div>
        </div>
        <div class="droplist-legend" aria-hidden="true">
            <span class="droplist-legend-item"><span class="drop-state drop-state-yes">&#10003;</span> Sim</span>
            <span class="droplist-legend-item"><span class="drop-state drop-state-no">&#10007;</span> Nao</span>
            <span class="droplist-legend-item"><span class="drop-state drop-state-random">&#8644;</span> Random</span>
        </div>
        <div class="droplist-summary" id="droplistSummary">No bag selected.</div>
    </div>

    <div class="rankings-table-frame droplist-table-frame" id="droplistTableWrap" style="display:none;">
        <table class="rankings-table droplist-table" id="droplistTable">
            <thead>
                <tr>
                    <td>Name</td>
                    <td>Drop %</td>
                    <td>Max Lv</td>
                    <td>Sockets</td>
                    <td>Luck</td>
                    <td>Skill</td>
                    <td>Exc</td>
                    <td>Anc</td>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <div class="news-pagination" id="droplistPagination" style="display:none;"></div>
</div>

<script>
(function() {
    'use strict';

    var bagOptions = <?php echo $bagOptionsJson; ?> || [];
    var pageSize = 20;
    var currentPage = 1;
    var allItems = [];
    var filteredItems = [];

    var bagSelect = document.getElementById('droplistBag');
    var searchInput = document.getElementById('droplistSearch');
    var typeSelect = document.getElementById('droplistType');
    var tableWrap = document.getElementById('droplistTableWrap');
    var tableBody = document.querySelector('#droplistTable tbody');
    var summary = document.getElementById('droplistSummary');
    var pagination = document.getElementById('droplistPagination');

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function fillBagOptions() {
        for (var i = 0; i < bagOptions.length; i++) {
            var opt = document.createElement('option');
            opt.value = bagOptions[i].value;
            opt.textContent = bagOptions[i].text;
            bagSelect.appendChild(opt);
        }
    }

    function setSummary(text) {
        summary.textContent = text;
    }

    function toPercent(value) {
        if (value === undefined || value === null || value === '') return '-';
        var num = Number(value);
        if (!isFinite(num)) return '-';
        if (Math.abs(num) >= 1) {
            return num.toFixed(2) + '%';
        }
        return num.toFixed(4) + '%';
    }

    function stateIcon(state) {
        if (state === 'yes') {
            return '<span class="drop-state drop-state-yes" title="Sim">&#10003;</span>';
        }

        if (state === 'random') {
            return '<span class="drop-state drop-state-random" title="Random">&#8644;</span>';
        }

        if (state === 'no') {
            return '<span class="drop-state drop-state-no" title="Nao">&#10007;</span>';
        }

        return '<span class="drop-state drop-state-na">-</span>';
    }

    function renderSocketMax(item) {
        if (item.socket_max === undefined || item.socket_max === null) {
            return '-';
        }

        if (item.socket_max === 'random') {
            return '<span class="drop-socket-random" title="Random">&#8644;</span>';
        }

        return escapeHtml(item.socket_max);
    }

    function renderMaxLevel(item) {
        if (item.max_level !== undefined && item.max_level !== null) {
            return escapeHtml(item.max_level);
        }

        if (item.max !== undefined && item.max !== null) {
            return escapeHtml(item.max);
        }

        return '-';
    }

    function renderTablePage() {
        tableBody.innerHTML = '';

        if (!filteredItems.length) {
            tableBody.innerHTML = '<tr><td colspan="8" class="droplist-empty-row">No results found.</td></tr>';
            return;
        }

        var start = (currentPage - 1) * pageSize;
        var pageItems = filteredItems.slice(start, start + pageSize);

        var html = '';
        for (var i = 0; i < pageItems.length; i++) {
            var item = pageItems[i];

            html += '<tr>' +
                '<td>' + escapeHtml(item.name || '-') + '</td>' +
                '<td class="text-center">' + escapeHtml(toPercent(item.drop_percent)) + '</td>' +
                '<td class="text-center">' + renderMaxLevel(item) + '</td>' +
                '<td class="text-center">' + renderSocketMax(item) + '</td>' +
                '<td class="text-center">' + stateIcon(item.luck_state) + '</td>' +
                '<td class="text-center">' + stateIcon(item.skill_state) + '</td>' +
                '<td class="text-center">' + stateIcon(item.exc_state) + '</td>' +
                '<td class="text-center">' + stateIcon(item.anc_state) + '</td>' +
                '</tr>';
        }

        tableBody.innerHTML = html;
    }

    function renderPagination() {
        pagination.innerHTML = '';

        var totalPages = Math.ceil(filteredItems.length / pageSize);
        if (totalPages <= 1) {
            pagination.style.display = 'none';
            return;
        }

        pagination.style.display = 'flex';

        for (var p = 1; p <= totalPages; p++) {
            var link = document.createElement('button');
            link.type = 'button';
            link.className = 'pagination-link' + (p === currentPage ? ' active' : '');
            link.textContent = String(p);
            (function(pageNumber) {
                link.addEventListener('click', function() {
                    currentPage = pageNumber;
                    renderTablePage();
                    renderPagination();
                });
            })(p);
            pagination.appendChild(link);
        }
    }

    function applyFilters() {
        var q = (searchInput.value || '').toLowerCase().trim();
        var selectedType = (typeSelect.value || '').toLowerCase().trim();

        filteredItems = allItems.filter(function(item) {
            if (selectedType && String(item.type || '').toLowerCase() !== selectedType) {
                return false;
            }

            if (!q) return true;

            var combined = [
                item.name,
                item.type,
                item.cat,
                item.index,
                item.cat_index,
                item.option_summary,
                item.luck_state,
                item.skill_state,
                item.exc_state,
                item.anc_state,
                item.socket_max,
                item.raw,
                item.min_level,
                item.max_level,
                item.min,
                item.max
            ].join(' ').toLowerCase();

            return combined.indexOf(q) !== -1;
        });

        currentPage = 1;
        renderTablePage();
        renderPagination();

        setSummary('Mostrando ' + filteredItems.length + ' de ' + allItems.length + ' registros.');
    }

    function loadBag(fileName) {
        if (!fileName) {
            allItems = [];
            filteredItems = [];
            tableWrap.style.display = 'none';
            pagination.style.display = 'none';
            setSummary('Nenhuma bag selecionada.');
            return;
        }

        setSummary('Carregando dados da bag...');

        var url = new URL('<?php echo __BASE_URL__; ?>api/droplist.php', window.location.origin);
        url.searchParams.set('bag', fileName);

        fetch(url.toString())
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.error) {
                    setSummary(data.error);
                    allItems = [];
                    filteredItems = [];
                    tableWrap.style.display = 'none';
                    pagination.style.display = 'none';
                    return;
                }

                allItems = Array.isArray(data.items) ? data.items : [];
                tableWrap.style.display = 'block';
                applyFilters();
            })
            .catch(function() {
                setSummary('Nao foi possivel carregar a bag.');
                allItems = [];
                filteredItems = [];
                tableWrap.style.display = 'none';
                pagination.style.display = 'none';
            });
    }

    bagSelect.addEventListener('change', function() {
        loadBag(this.value);
    });

    searchInput.addEventListener('input', applyFilters);
    typeSelect.addEventListener('change', applyFilters);

    fillBagOptions();
})();
</script>
