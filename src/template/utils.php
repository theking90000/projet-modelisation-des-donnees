<?php 
function print_header($nom, $actions="", $icon="house.svg", $back="/") : string {
    if(isset($_GET["noLayout"])) return "";

    return '
    <div class="header">
        <h2>
        <a href="'.$back.'" class="icon">
            <img src="/assets/images/'.$icon.'">
        </a>
        ' . htmlspecialchars($nom) . '</h2>
        <div>' . $actions . '</div>
    </div>
    ';
}

function print_add_transaction($portfolio_id) : string {
    $path_only = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    if (preg_match('~instrument/([^/]+)~', $path_only, $matches)) {
        $isin = $matches[1];
    }

    return '
    <script>
        add_callback("-1", (value, label) => {
            // close(document.querySelector("#ajout-transaction"));
            window.location.reload();
        });
    </script>

    <a href="#" class="button" data-open="#ajout-transaction">Ajouter une transaction</a>
    <div id="ajout-transaction" class="popup" data-popup="1" style="display: none" data-load="/portfolio/' . htmlspecialchars($portfolio_id) . '/ajout-transaction?callback_id=-1&form=1&nopopup=1'.
    (isset($isin)?'&instrument='.htmlspecialchars($isin) : '')
    .'"></div>
    ';
}

function create_button ($content, $href, $icon="") : string {
    return "<a href=\"". $href ."\" class=\"button\">". htmlspecialchars($content) . $icon . "</a>";
}

function image ($name, $w=20, $h=20): string {
    return '<img src="/assets/images/'. urlencode($name) . '" width="'.$w.'" height="'.$h.'" />';
}

function print_portfolio_header($id, $nom, $back="/") {
    return print_header($nom, print_add_transaction($id) . create_button("Paramètres", "/portfolio/$id/parametres", image("arrow-right.svg")), "arrow-left.svg", $back);
}

function print_portfolio_header_back($id, $nom) {
    return print_header($nom, create_button("Retour", "/portfolio/$id", image("arrow-left.svg")));
}

function with_color($elem, $positive, $value, $suffix) {
    $str = "<$elem class=\"";
    if($positive) {
        $str .= "success\"> +";
    } else {
        $str .= "danger\"> -";
    }
    return $str.htmlspecialchars($value).$suffix. "</$elem>\n";
}

function with_color_val($elem, $value, $suffix='') {
    return with_color($elem, $value >= 0, abs($value), $suffix);
}