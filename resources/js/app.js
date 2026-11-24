import Alpine from "alpinejs";
import posTerminal from "./pos-terminal";
import productForm from "./product-form";
import salesChart from "./sales-chart";
import "./barcode-scanner";
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";

window.Alpine = Alpine;
window.flatpickr = flatpickr;

Alpine.data("posTerminal", posTerminal);
Alpine.data("productForm", productForm);
Alpine.data("salesChart", salesChart);
Alpine.start();

document.addEventListener("DOMContentLoaded", () => {
    flatpickr(".datepicker", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "F j, Y",
        allowInput: true,
    });
});
