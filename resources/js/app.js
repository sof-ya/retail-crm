import './bootstrap';
import { createApp } from 'vue';
import OrdersList from './components/OrdersList.vue';
import OrderCreate from './components/OrderCreate.vue';
import OrderEdit from './components/OrderEdit.vue';
import StockMovementsList from './components/StockMovementsList.vue';

const app = createApp({
    data() {
        return {
            currentView: 'list',
            editOrderId: null,
        };
    },
    methods: {
        viewOrder(id) {
            this.editOrderId = id;
            this.currentView = 'edit';
        },
        backToList() {
            this.currentView = 'list';
            this.editOrderId = null;
        },
    },
    components: {
        OrdersList,
        OrderCreate,
        OrderEdit,
        StockMovementsList,
    },
});

app.mount('#app');
