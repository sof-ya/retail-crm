<template>
    <div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Создать заказ</h1>
            <button @click="$emit('back')" class="text-indigo-600 hover:text-indigo-800">
                &larr; Назад к списку
            </button>
        </div>

        <!-- Success Message -->
        <div v-if="success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            Заказ #{{ createdOrderId }} успешно создан!
        </div>

        <!-- Error Message -->
        <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <p class="font-bold">Ошибка:</p>
            <p>{{ error }}</p>
            <ul v-if="validationErrors" class="mt-2 list-disc list-inside">
                <li v-for="(errors, field) in validationErrors" :key="field">
                    {{ field }}: {{ errors.join(', ') }}
                </li>
            </ul>
            <ul v-if="unavailableProducts.length > 0" class="mt-2 list-disc list-inside">
                <li v-for="product in unavailableProducts" :key="product.product_id">
                    Товар #{{ product.product_id }}: доступно {{ product.available }}, запрошено {{ product.required }}
                </li>
            </ul>
        </div>

        <form @submit.prevent="submitOrder" class="bg-white rounded-lg shadow p-6">
            <!-- Customer and Warehouse -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Клиент *</label>
                    <select v-model="form.customer_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Выберите клиента</option>
                        <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                            {{ customer.name }} (ID: {{ customer.id }})
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Склад *</label>
                    <select v-model="form.warehouse_id" @change="onWarehouseChange" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Выберите склад</option>
                        <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                            {{ warehouse.name }} (ID: {{ warehouse.id }})
                        </option>
                    </select>
                </div>
            </div>

            <!-- Items -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-medium text-gray-900">Позиции заказа</h2>
                    <button type="button" @click="addItem" :disabled="!form.warehouse_id" class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        + Добавить позицию
                    </button>
                </div>

                <div v-if="!form.warehouse_id" class="text-gray-500 text-center py-4 border-2 border-dashed border-gray-300 rounded-lg">
                    Сначала выберите склад
                </div>

                <div v-else-if="form.items.length === 0" class="text-gray-500 text-center py-4 border-2 border-dashed border-gray-300 rounded-lg">
                    Добавьте хотя бы одну позицию
                </div>

                <div v-for="(item, index) in form.items" :key="index" class="flex items-center gap-4 mb-3 p-3 bg-gray-50 rounded-lg">
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 mb-1">Товар</label>
                        <select v-model="item.product_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Выберите товар</option>
                            <option v-for="product in products" :key="product.id" :value="product.id">
                                {{ product.name }} ({{ product.price }} ₽) - Остаток: {{ product.stock }}
                            </option>
                        </select>
                    </div>
                    <div class="w-32">
                        <label class="block text-xs text-gray-500 mb-1">Количество</label>
                        <input v-model.number="item.count" type="number" min="1" :max="getMaxCount(item.product_id)" required placeholder="0" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <button type="button" @click="removeItem(index)" class="mt-5 text-red-600 hover:text-red-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
                <button type="submit" :disabled="submitting || form.items.length === 0" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg v-if="submitting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ submitting ? 'Создание...' : 'Создать заказ' }}
                </button>
            </div>
        </form>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'OrderCreate',
    emits: ['back'],
    data() {
        return {
            customers: [],
            warehouses: [],
            products: [],
            form: {
                customer_id: '',
                warehouse_id: '',
                items: [],
            },
            submitting: false,
            success: false,
            error: null,
            validationErrors: null,
            unavailableProducts: [],
            createdOrderId: null,
        };
    },
    mounted() {
        this.loadData();
    },
    methods: {
        async loadData() {
            try {
                const [customersRes, warehousesRes] = await Promise.all([
                    axios.get('/api/customers'),
                    axios.get('/api/warehouses'),
                ]);
                this.customers = customersRes.data.data;
                this.warehouses = warehousesRes.data.data;
            } catch (error) {
                this.error = 'Ошибка загрузки данных';
            }
        },
        async onWarehouseChange() {
            this.products = [];
            this.form.items = [];

            if (!this.form.warehouse_id) return;

            try {
                const response = await axios.get(`/api/warehouses/${this.form.warehouse_id}/products`);
                this.products = response.data.data;
            } catch (error) {
                this.error = 'Ошибка загрузки товаров';
            }
        },
        getMaxCount(productId) {
            const product = this.products.find(p => p.id === productId);
            return product ? product.stock : 999999;
        },
        addItem() {
            this.form.items.push({
                product_id: '',
                count: 1,
            });
        },
        removeItem(index) {
            this.form.items.splice(index, 1);
        },
        async submitOrder() {
            this.submitting = true;
            this.error = null;
            this.validationErrors = null;
            this.unavailableProducts = [];
            this.success = false;

            try {
                const response = await axios.post('/api/orders', this.form);
                this.success = true;
                this.createdOrderId = response.data.data.id;
                this.resetForm();
            } catch (error) {
                if (error.response?.status === 422) {
                    this.error = error.response.data.message;
                    this.validationErrors = error.response.data.errors;
                    if (error.response.data.unavailable_products) {
                        this.unavailableProducts = error.response.data.unavailable_products;
                    }
                } else {
                    this.error = error.response?.data?.message || 'Ошибка создания заказа';
                }
            } finally {
                this.submitting = false;
            }
        },
        resetForm() {
            this.form = {
                customer_id: '',
                warehouse_id: '',
                items: [],
            };
            this.products = [];
        },
    },
};
</script>
