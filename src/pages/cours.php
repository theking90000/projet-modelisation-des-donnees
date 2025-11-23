<?php
require_once __DIR__ . '/../lib/db.php';

$database = Database::instance();

$instrument = $database->execute("SELECT * FROM Instrument_Financier WHERE isin=?", [$isin])->fetch();
?>
<script src="https://cdn.jsdelivr.net/npm/luxon@3.4.4"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1.3.1"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-chart-financial"></script>
<script src="/assets/graph.js" defer></script>

<div class="graph" style="height:100vh; display: flex; flex-direction: column; align-items: center; justify-content: center">
    <canvas id="graph" data="<?= $isin ?>" data-type="cours" currency="€" label="<?= $instrument["nom"]?>" width="600" height="300" style="max-width: 900px; max-height:600px;"></canvas>
    <div style="display: flex; flex-direction: row">
        <button id="week">1 Semaine</button>
        <button id="month">1 Mois</button>
    </div>
</div>