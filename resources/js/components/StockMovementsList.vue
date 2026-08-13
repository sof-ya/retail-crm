<template>
    <div class="p-6">
        <h1 class="text-2xl font-bold mb-6">Движение товаров</h1>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div v-if="warehouses.length > 0">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Склад</label>
                    <select v-model="filters.warehouse_id" @change="fetchMovements" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Все склады</option>
                        <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                            {{ warehouse.name }}
                        </option>
                    </select>
                </div>
                <div v-if="products.length > 0">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Товар</label>
                    <select v-model="filters.product_id" @change="fetchMovements" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Все товары</option>
                        <option v-for="product in products" :key="product.id" :value="product.id">
                            {{ product.name }}
                        </option>
                    </select>
                </div>
                <div v-if="docTypes.length > 0">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Тип документа</label>
                    <select v-model="filters.doc_type" @change="fetchMovements" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Все типы</option>
                        <option v-for="docType in docTypes" :key="docType.value" :value="docType.value">
                            {{ docType.label }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Дата от</label>
                    <input v-model="filters.created_from" @change="fetchMovements" type="date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Дата до</label>
                    <input v-model="filters.created_to" @change="fetchMovements" type="date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">На странице</label>
                    <select v-model="filters.per_page" @change="fetchMovements" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button @click="resetFilters" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 border border-gray-300 rounded-md hover:bg-gray-50">
                        Сбросить фильтры
                    </button>
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

        <!-- Movements Table -->
        <div v-else class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Товар</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Склад</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Тип</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Документ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Количество</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="movement in movements" :key="movement.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ movement.id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ movement.product_name }}
                            <div class="text-xs text-gray-500">ID: {{ movement.product_id }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ movement.warehouse_name }}
                            <div class="text-xs text-gray-500">ID: {{ movement.warehouse_id }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="docTypeClass(movement.doc_type)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                {{ docTypeLabel(movement.doc_type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            #{{ movement.doc_id }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <span :class="movement.quantity > 0 ? 'text-green-600' : 'text-red-600'">
                                {{ movement.quantity > 0 ? '+' : '' }}{{ movement.quantity }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(movement.created_at) }}</td>
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
    name: 'StockMovementsList',
    data() {
        return {
            movements: [],
            warehouses: [],
            products: [],
            docTypes: [],
            loading: false,
            error: null,
            filters: {
                warehouse_id: '',
                product_id: '',
                doc_type: '',
                created_from: '',
                created_to: '',
                per_page: 15,
            },
            pagination: {
                current_page: 1,
                last_page: 1,
            },
        };
    },
    mounted() {
        this.loadFiltersData();
        this.fetchMovements();
    },
    methods: {
        async loadFiltersData() {
            try {
                const response = await axios.get('/api/stock-movements/filters');
                this.warehouses = response.data.warehouses;
                this.products = response.data.products;
                this.docTypes = response.data.doc_types;
            } catch (error) {
                console.error('Ошибка загрузки фильтров:', error);
            }
        },
        async fetchMovements() {
            this.loading = true;
            this.error = null;

            try {
                const params = {
                    page: this.pagination.current_page,
                    per_page: this.filters.per_page,
                };

                if (this.filters.warehouse_id) params.warehouse_id = this.filters.warehouse_id;
                if (this.filters.product_id) params.product_id = this.filters.product_id;
                if (this.filters.doc_type) params.doc_type = this.filters.doc_type;
                if (this.filters.created_from) params.created_from = this.filters.created_from;
                if (this.filters.created_to) params.created_to = this.filters.created_to;

                const response = await axios.get('/api/stock-movements', { params });

                this.movements = response.data.data;
                this.pagination = {
                    current_page: response.data.meta.current_page,
                    last_page: response.data.meta.last_page,
                };
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка загрузки данных';
            } finally {
                this.loading = false;
            }
        },
        changePage(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.pagination.current_page = page;
            this.fetchMovements();
        },
        resetFilters() {
            this.filters = {
                warehouse_id: '',
                product_id: '',
                doc_type: '',
                created_from: '',
                created_to: '',
                per_page: 15,
            };
            this.pagination.current_page = 1;
            this.fetchMovements();
        },
        docTypeClass(type) {
            const classes = {
                'App\\Models\\Order': 'bg-blue-100 text-blue-800',
                'App\\Models\\Supply': 'bg-green-100 text-green-800',
                'App\\Models\\Transfer': 'bg-purple-100 text-purple-800',
            };
            return classes[type] || 'bg-gray-100 text-gray-800';
        },
        docTypeLabel(type) {
            const labels = {
                'App\\Models\\Order': 'Заказ',
                'App\\Models\\Supply': 'Поставка',
                'App\\Models\\Transfer': 'Перемещение',
            };
            return labels[type] || type;
        },
        formatDate(date) {
            if (!date) return '-';
            return new Date(date).toLocaleString('ru-RU');
        },
    },
};
</script>
