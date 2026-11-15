import Alpine from "alpinejs";
import posTerminal from "./pos-terminal";
import salesChart from "./sales-chart";

window.Alpine = Alpine;

Alpine.data("posTerminal", posTerminal);
Alpine.data("salesChart", salesChart);
Alpine.start();
