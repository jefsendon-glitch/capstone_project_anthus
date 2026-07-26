import Alpine from 'alpinejs';
import Swal from 'sweetalert2';
import flatpickr from 'flatpickr';
import TomSelect from 'tom-select';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

window.Alpine = Alpine;
window.Swal = Swal;
window.flatpickr = flatpickr;
window.TomSelect = TomSelect;
window.Chart = Chart;

window.purchaseOrderForm = (payload) => {
    const data = JSON.parse(payload);

    return {
        products: data.products ?? [],
        consumables: data.consumables ?? [],
        items: data.items?.length ? data.items : [
            { itemable_type: 'product', itemable_id: '', quantity_ordered: 1, unit_cost: 0 },
        ],
        addItem() {
            this.items.push({ itemable_type: 'product', itemable_id: '', quantity_ordered: 1, unit_cost: 0 });
        },
        removeItem(index) {
            if (this.items.length > 1) this.items.splice(index, 1);
        },
    };
};

Alpine.store('theme', {
    dark: localStorage.getItem('theme') === 'dark'
        || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches),

    toggle() {
        this.dark = !this.dark;
        localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        document.documentElement.classList.toggle('dark', this.dark);
    },
});

Alpine.store('sidebar', {
    collapsed: localStorage.getItem('sidebar-collapsed') === '1',

    toggle() {
        this.collapsed = !this.collapsed;
        localStorage.setItem('sidebar-collapsed', this.collapsed ? '1' : '0');
    },
});

Alpine.start();

function initWidgets() {
    document.querySelectorAll('[data-flatpickr]').forEach((el) => {
        if (!el._flatpickr) flatpickr(el);
    });

    document.querySelectorAll('[data-tom-select]').forEach((el) => {
        if (!el.tomselect) new TomSelect(el);
    });
}

document.addEventListener('DOMContentLoaded', initWidgets);
document.addEventListener('widgets:refresh', initWidgets);
