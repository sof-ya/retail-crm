<template>
    <div class="p-6">
        <h1 class="text-2xl font-bold mb-6">Заказы</h1>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Статус</label>
                    <select v-model="filters.status" @change="fetchOrders" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Все</option>
                        <option value="active">Активный</option>
                        <option value="completed">Завершён</option>
                        <option value="canceled">Отменён</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ID клиента</label>
                    <input v-model="filters.customer_id" @input="fetchOrders" type="number" placeholder="ID клиента" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ID склада</label>
                    <input v-model="filters.warehouse_id" @input="fetchOrders" type="number" placeholder="ID склада" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">На странице</label>
                    <select v-model="filters.per_page" @change="fetchOrders" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ error }}
        </div>

        <!-- Orders Table -->
        <div v-else class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Клиент</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Склад</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Создан</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Позиции</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="order in orders" :key="order.id" class="hover:bg-gray-50 cursor-pointer transition-colors duration-150">
                        <td @click="$emit('edit', order.id)" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ order.id }}</td>
                        <td @click="$emit('edit', order.id)" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ order.customer_name }}
                            <div class="text-xs text-gray-500">ID: {{ order.customer_id }}</div>
                        </td>
                        <td @click="$emit('edit', order.id)" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ order.warehouse_name }}
                            <div class="text-xs text-gray-500">ID: {{ order.warehouse_id }}</div>
                        </td>
                        <td @click="$emit('edit', order.id)" class="px-6 py-4 whitespace-nowrap">
                            <span :class="statusClass(order.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                {{ statusLabel(order.status) }}
                            </span>
                        </td>
                        <td @click="$emit('edit', order.id)" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(order.created_at) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="relative inline-block">
                                <button @click.stop="toggleItems(order.id)" class="w-8 h-8 rounded-full border border-gray-300 bg-white flex items-center justify-center hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <svg :class="{ 'rotate-180': expandedOrder === order.id }" class="w-4 h-4 text-gray-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div v-if="expandedOrder === order.id" class="absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                                    <div class="py-2 px-3">
                                        <div v-if="order.items.length === 0" class="text-sm text-gray-500 py-2">
                                            Нет позиций
                                        </div>
                                        <div v-else>
                                            <div v-for="item in order.items" :key="item.id" class="py-1.5 border-b border-gray-100 last:border-0">
                                                <div class="text-sm font-medium text-gray-900">{{ item.product_name }}</div>
                                                <div class="text-xs text-gray-500">Количество: {{ item.count }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="mt-6 flex justify-center">
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    Назад
                </button>
                <button v-for="page in pagination.last_page" :key="page" @click="changePage(page)" :class="page === pagination.current_page ? 'bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                    {{ page }}
                </button>
                <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    Вперёд
                </button>
            </nav>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'OrdersList',
    emits: ['edit'],
    data() {
        return {
            orders: [],
            loading: false,
            error: null,
            expandedOrder: null,
            filters: {
                status: '',
                customer_id: '',
                warehouse_id: '',
                per_page: 15,
            },
            pagination: {
                current_page: 1,
                last_page: 1,
            },
        };
    },
    mounted() {
        this.fetchOrders();
        document.addEventListener('click', this.closeItems);
    },
    beforeUnmount() {
        document.removeEventListener('click', this.closeItems);
    },
    methods: {
        async fetchOrders() {
            this.loading = true;
            this.error = null;

            try {
                const params = {
                    page: this.pagination.current_page,
                    per_page: this.filters.per_page,
                };

                if (this.filters.status) params.status = this.filters.status;
                if (this.filters.customer_id) params.customer_id = this.filters.customer_id;
                if (this.filters.warehouse_id) params.warehouse_id = this.filters.warehouse_id;

                const response = await axios.get('/api/orders', { params });

                this.orders = response.data.data;
                this.pagination = {
                    current_page: response.data.meta.current_page,
                    last_page: response.data.meta.last_page,
                };
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка загрузки заказов';
            } finally {
                this.loading = false;
            }
        },
        changePage(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.pagination.current_page = page;
            this.fetchOrders();
        },
        toggleItems(orderId) {
            this.expandedOrder = this.expandedOrder === orderId ? null : orderId;
        },
        closeItems(event) {
            if (!event.target.closest('.relative')) {
                this.expandedOrder = null;
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
        formatDate(date) {
            if (!date) return '-';
            return new Date(date).toLocaleString('ru-RU');
        },
    },
};
</script>
