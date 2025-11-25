<?php

/**
 * Class Helper "affichage_table2
 * Extraite de la logique de contenu_portfolio.php
 * Afin de généraliser l'affichage de données.
 */

class TableHelper {
    private $columns = [];
    private $baseUrl;
    private $containerId;
    private $filterInputId;
    private $defaultSort;
    private $defaultSortType = 'desc';
    private $perPage = 20;

    public function __construct(string $baseUrl, string $containerId, string $filterInputId) {
        $this->baseUrl = $baseUrl;
        $this->containerId = $containerId;
        $this->filterInputId = $filterInputId;
    }

    public function addColumn(string $key, string $label, array $options = []): self {
        $this->columns[$key] = array_merge([
            'label' => $label,
            'sortable' => true,
            'renderer' => null, // function($row, $value)
            'type' => 'text' // text, colored_number, custom
        ], $options);
        return $this;
    }

    public function setDefaultSort(string $key, string $type = 'desc'): self {
        $this->defaultSort = $key;
        $this->defaultSortType = $type;
        return $this;
    }

    public function setPerPage(int $limit): self {
        $this->perPage = $limit;
        return $this;
    }

    public function render(callable $dataProvider, callable $countProvider) {
        $recherche = $_GET["recherche"] ?? "";
        $page = intval($_GET["page"] ?? 0);
        $limit = intval($_GET["perPage"] ?? $this->defaultPerPage ?? 20);
        $orderBy = $_GET["sort"] ?? $this->defaultSort;
        $orderByType = $_GET["sortType"] ?? $this->defaultSortType;
        $hideSort = isset($_GET["hideSort"]);

        if ($orderBy && !array_key_exists($orderBy, $this->columns)) {
            // Fallback or error
            $orderBy = array_key_first($this->columns);
        }

        $data = $dataProvider($page, $limit, $orderBy, $orderByType, $recherche);

        echo "<table class=\"data-table\">\n<thead>\n<tr>\n";
        
        foreach ($this->columns as $key => $col) {
            echo "<th ";
            if (!$hideSort && $col['sortable']) {
                $nextSortType = ($orderByType == "desc" && $orderBy == $key) ? "asc" : "desc";
                $url = $this->buildUrl($this->baseUrl, [
                    'sort' => $key,
                    'sortType' => $nextSortType
                ]);
                
                echo "style=\"cursor: pointer;\" onclick=\"search_ajax('{$this->filterInputId}', '{$this->containerId}', 0, '$url');\"";
            }
            echo ">" . htmlspecialchars($col['label']);
            
            if (!$hideSort && $orderBy == $key) {
                echo ($orderByType == "desc") ? "&#9660;&nbsp;" : "&#9650;&nbsp;";
            }
            echo "</th>\n";
        }
        echo "</tr>\n</thead>\n<tbody>\n";

        foreach ($data as $row) {
            echo "<tr>";
            foreach ($this->columns as $key => $col) {
                $val = $row[$key] ?? null;
                $renderer = $col['renderer'];
                
                if ($renderer !== null) {
                    echo "<td>" . call_user_func($renderer, $row, $val) . "</td>";
                } elseif ($col['type'] === 'colored_number') {
                    echo with_color_val("td", $val);
                } else {
                    echo "<td>" . htmlspecialchars($val??'') . "</td>";
                }
            }
            echo "</tr>\n";
        }
        echo "</tbody>\n</table>\n";

        if (!isset($_GET["noPagination"])) {
            $total = $countProvider($recherche);
            $this->renderPagination($page, $limit, $total, $orderBy, $orderByType, $recherche);
        }
    }

    private function renderPagination($page, $limit, $total, $orderBy, $orderByType, $recherche) {
        $nav = function ($targetPage, $text) use ($orderBy, $orderByType, $recherche) {
            $url = $this->buildUrl($this->baseUrl, [
                'sort' => $orderBy,
                'sortType' => $orderByType,
                'recherche' => $recherche
            ]);
            
            echo "<a href=\"#\" onclick=\"search_ajax('{$this->filterInputId}', '{$this->containerId}', $targetPage, '$url'); return false;\">$text</a>\n";
        };

        if ($page > 0) $nav($page - 1, "Page précédente");
        if (($page + 1) * $limit < $total) $nav($page + 1, "Page suivante");
    }

    private function buildUrl($base, $params) {
        $query = http_build_query($params);
        return $base . (strpos($base, '?') === false ? '?' : '&') . $query;
    }
}
