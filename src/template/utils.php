<?php 
function print_header($nom, $actions="") : string {
    return '
    <div class="header">
        <h2>' . htmlspecialchars($nom) . '</h2>
        <div>' . $actions . '</div>
    </div>
    ';
}

function print_add_transaction($portfolio_id) : string {
    return '
    <script>
        add_callback("-1", (value, label) => {
            // close(document.querySelector("#ajout-transaction"));
            window.location.reload();
        });
    </script>

    <a href="#" class="button" data-open="#ajout-transaction">Ajouter une transaction</a>
    <div id="ajout-transaction" class="popup" data-popup="1" style="display: none" data-load="/portfolio/' . htmlspecialchars($portfolio_id) . '/ajout-transaction?callback_id=-1&form=1&nopopup=1"></div>
    ';
}

function create_button ($content, $href, $icon="") : string {
    return "<a href=\"". $href ."\" class=\"button\">". htmlspecialchars($content) . $icon . "</a>";
}

function image ($name, $w=20, $h=20): string {
    return '<img src="/assets/images/'. urlencode($name) . '" width="'.$w.'" height="'.$h.'" />';
}

function print_portfolio_header($id, $nom) {
    return print_header($nom, print_add_transaction($id) . create_button("Paramètres", "/portfolio/$id/parametres", image("arrow-right.svg")));
}