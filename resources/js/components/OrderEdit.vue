<template>
    <div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Редактировать заказ #{{ orderId }}</h1>
            <button @click="$emit('back')" class="text-indigo-600 hover:text-indigo-800">
                &larr; Назад к списку
            </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ error }}
        </div>

        <template v-else>
            <!-- Success Message -->
            <div v-if="success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ successMessage }}
            </div>

            <!-- Validation Errors -->
            <div v-if="validationErrors" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <p class="font-bold">Ошибка валидации:</p>
                <ul class="mt-2 list-disc list-inside">
                    <li v-for="(errors, field) in validationErrors" :key="field">
                        {{ field }}: {{ errors.join(', ') }}
                    </li>
                </ul>
            </div>

            <!-- Order Info -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <span class="text-sm text-gray-500">Статус</span>
                        <div class="mt-1">
                            <span :class="statusClass(order.status)" class="px-3 py-1 inline-flex text-sm font-semibold rounded-full">
                                {{ statusLabel(order.status) }}
                            </span>
                        </div>
                    </div>
                    <div v-if="order.status !== 'active'">
                        <span class="text-sm text-gray-500">Клиент</span>
                        <div class="mt-1 font-medium">{{ order.customer_name }} (ID: {{ order.customer_id }})</div>
                    </div>
                    <div v-if="order.status !== 'active'">
                        <span class="text-sm text-gray-500">Склад</span>
                        <div class="mt-1 font-medium">{{ order.warehouse_name }} (ID: {{ order.warehouse_id }})</div>
                    </div>
                    <div v-if="order.status === 'active'">
                        <label class="text-sm text-gray-500">Клиент</label>
                        <select v-model="form.customer_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                {{ customer.name }} (ID: {{ customer.id }})
                            </option>
                        </select>
                    </div>
                    <div v-if="order.status === 'active'">
                        <label class="text-sm text-gray-500">Склад</label>
                        <select v-model="form.warehouse_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="" disabled>Выберите склад</option>
                            <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                                {{ warehouse.name }} (ID: {{ warehouse.id }})
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Status Actions -->
            <div v-if="order.status === 'active' || order.status === 'canceled'" class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Действия</h2>
                <div class="flex flex-wrap gap-3">
                    <button v-if="order.status === 'active'" @click="completeOrder" :disabled="actionLoading" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 disabled:opacity-50">
                        <svg v-if="actionLoading && actionType === 'complete'" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Завершить
                    </button>
                    <button v-if="order.status === 'active'" @click="cancelOrder" :disabled="actionLoading" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 disabled:opacity-50">
                        <svg v-if="actionLoading && actionType === 'cancel'" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Отменить
                    </button>
                    <button v-if="order.status === 'canceled'" @click="resumeOrder" :disabled="actionLoading" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50">
                        <svg v-if="actionLoading && actionType === 'resume'" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Возобновить
                    </button>
                </div>
            </div>

            <!-- Edit Items (only for active orders) -->
            <div v-if="order.status === 'active'" class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-medium text-gray-900">Позиции заказа</h2>
                    <button type="button" @click="addItem" class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                        + Добавить позицию
                    </button>
                </div>

                <div v-if="form.items.length === 0" class="text-gray-500 text-center py-4 border-2 border-dashed border-gray-300 rounded-lg">
                    Нет позиций в заказе
                </div>

                <div v-for="(item, index) in form.items" :key="index" class="flex items-center gap-4 mb-3 p-3 bg-gray-50 rounded-lg">
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 mb-1">Товар</label>
                        <select v-model="item.product_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Выберите товар</option>
                            <option v-for="product in products" :key="product.id" :value="product.id">
                                {{ product.name }} ({{ product.price }} ₽)
                            </option>
                        </select>
                    </div>
                    <div class="w-32">
                        <label class="block text-xs text-gray-500 mb-1">Количество</label>
                        <input v-model.number="item.count" type="number" min="1" required placeholder="0" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <button type="button" @click="removeItem(index)" class="mt-5 text-red-600 hover:text-red-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>

                <div class="mt-6 flex justify-end">
                    <button @click="updateOrder" :disabled="updateLoading" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50">
                        <svg v-if="updateLoading" class="animate-spin -ml-1 mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Сохранить изменения
                    </button>
                </div>
            </div>

            <!-- Read-only items for completed/canceled orders -->
            <div v-else class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Позиции заказа</h2>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Товар</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Количество</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="item in order.items" :key="item.id">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.product_name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.count }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'OrderEdit',
    props: {
        orderId: {
            type: Number,
            required: true,
        },
    },
    emits: ['back'],
    data() {
        return {
            order: {},
            customers: [],
            warehouses: [],
            products: [],
            form: {
                customer_id: null,
                warehouse_id: null,
                items: [],
            },
            loading: true,
            actionLoading: false,
            updateLoading: false,
            actionType: null,
            error: null,
            success: false,
            successMessage: '',
            validationErrors: null,
        };
    },
    mounted() {
        this.loadData();
    },
    methods: {
        async loadData() {
            this.loading = true;
            this.error = null;

            try {
                const [orderRes, customersRes, warehousesRes, productsRes] = await Promise.all([
                    axios.get(`/api/orders/${this.orderId}`),
                    axios.get('/api/customers'),
                    axios.get('/api/warehouses'),
                    axios.get('/api/products'),
                ]);

                this.order = orderRes.data.data;
                this.customers = customersRes.data.data;
                this.warehouses = warehousesRes.data.data;
                this.products = productsRes.data.data;

                this.form.customer_id = this.order.customer_id;
                this.form.warehouse_id = this.order.warehouse_id;
                this.form.items = this.order.items.map(item => ({
                    product_id: item.product_id,
                    count: item.count,
                }));
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка загрузки данных';
            } finally {
                this.loading = false;
            }
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
        async updateOrder() {
            this.updateLoading = true;
            this.validationErrors = null;
            this.success = false;

            try {
                const response = await axios.put(`/api/orders/${this.orderId}`, this.form);
                this.order = response.data.data;
                this.form.customer_id = this.order.customer_id;
                this.form.warehouse_id = this.order.warehouse_id;
                this.success = true;
                this.successMessage = 'Заказ обновлён!';
            } catch (error) {
                if (error.response?.status === 422) {
                    this.validationErrors = error.response.data.errors;
                } else {
                    this.error = error.response?.data?.message || 'Ошибка обновления заказа';
                }
            } finally {
                this.updateLoading = false;
            }
        },
        async completeOrder() {
            await this.performAction('complete', 'Заказ завершён!');
        },
        async cancelOrder() {
            await this.performAction('cancel', 'Заказ отменён!');
        },
        async resumeOrder() {
            await this.performAction('resume', 'Заказ возобновлён!');
        },
        async performAction(action, message) {
            this.actionLoading = true;
            this.actionType = action;
            this.error = null;
            this.validationErrors = null;
            this.success = false;

            try {
                const response = await axios.patch(`/api/orders/${this.orderId}/${action}`);
                this.order = response.data.data;
                this.success = true;
                this.successMessage = message;
            } catch (error) {
                if (error.response?.status === 422) {
                    this.error = error.response.data.message;
                    if (error.response.data.unavailable_products) {
                        this.error += ': ' + error.response.data.unavailable_products.map(p => 
                            `Товар #${p.product_id} (нужно: ${p.required}, доступно: ${p.available})`
                        ).join('; ');
                    }
                } else {
                    this.error = error.response?.data?.message || `Ошибка выполнения действия`;
                }
            } finally {
                this.actionLoading = false;
                this.actionType = null;
            }
        },
        statusClass(status) {
            const classes = {
                active: 'bg-blue-100 text-blue-800',
                completed: 'bg-green-100 text-green-800',
                canceled: 'bg-red-100 text-red-800',
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },
        statusLabel(status) {
            const labels = {
                active: 'Активный',
                completed: 'Завершён',
                canceled: 'Отменён',
            };
            return labels[status] || status;
        },
    },
};
</script>
