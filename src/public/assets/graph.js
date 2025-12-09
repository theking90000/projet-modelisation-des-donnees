class Graph {
    constructor(canvas) {
        this.timeRange = "-7 days";
        this.canvas = canvas;
        this.currency = this.canvas.getAttribute("currency");
        this.label = this.canvas.getAttribute("label");
        this.chartType = this.canvas.getAttribute("type");
        this.ctx = this.canvas.getContext("2d");
    }

    async fetchData() {
        let source = this.canvas.getAttribute("data");
        let type = this.canvas.getAttribute("data-type");
        this.data = await fetch(`/donnees/${type}/${source}?range=${this.timeRange}`).then(r => r.json());
    }

    async changeTimeRange(timeRange) {
        this.timeRange = timeRange;
        await this.fetchData();
        this.drawGraph();
    }

    drawGraph() {
        if (this.data === undefined || this.data === null) {
            console.log("Données du graphiques non initialisées.")
            return;
        }

        if (this.chart === undefined) {
            switch (this.chartType) {
                case "candlestick":
                    this.chart = new Chart(this.ctx, {
                        type: "candlestick",
                        data: {
                            datasets: [{
                                label: this.label,
                                data: this.data,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            parsing: false,
                            scales:
                                {
                                    x: {
                                        type: "time", time:
                                            {
                                                unit: "day"
                                            }
                                    }
                                    ,
                                    y: {
                                        ticks: {
                                            callback: value => value.toFixed(2) + " " + this.currency
                                        }
                                    }
                                    ,
                                }
                            ,
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        label: ctx => {
                                            const ohlc = ctx.raw;
                                            return [
                                                `Ouverture: ${ohlc.o} ` + this.currency,
                                                `Maximum: ${ohlc.h} ` + this.currency,
                                                `Minimum: ${ohlc.l} ` + this.currency,
                                                `Fermeture: ${ohlc.c} ` + this.currency,
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    })
                    ;
                    break;
                case "line":
                    this.chart = new Chart(this.ctx, {
                        type: "line",
                        data: {
                            datasets: [{
                                label: this.label,
                                data: this.data,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            parsing: false,
                            scales:
                                {
                                    x: {
                                        type: "time", time:
                                            {
                                                unit: "day"
                                            }
                                    }
                                    ,
                                    y: {
                                        ticks: {
                                            callback: value => value.toFixed(2) + " " + this.currency
                                        }
                                    }
                                    ,
                                }
                            ,
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        label: ctx => {
                                            const val = ctx.raw;
                                            return [
                                                `Valeur: ${val.y} ` + this.currency,
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    })
                    ;
                    break;

            }

        } else {
            this.chart.data.datasets[0].data = this.data;
            this.chart.update();
        }

    }
}

async function main(canvas) {
    await graph.fetchData();
    graph.drawGraph();
}

const canvas = document.getElementById("graph");

const weekButton = document.getElementById("week");
const monthButton = document.getElementById("month");
const yearButton = document.getElementById("year");

const graph = new Graph(canvas);

if (canvas !== undefined && canvas !== null) {
    main(canvas);

    weekButton.addEventListener("click", async () => {
        await graph.changeTimeRange("-7 days");
    });

    monthButton.addEventListener("click", async () => {
        await graph.changeTimeRange("-1 month");
    });

    yearButton.addEventListener("click", async () => {
        await graph.changeTimeRange("-1 year");
    });
}
