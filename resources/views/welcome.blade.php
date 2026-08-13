<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Retail CRM') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gray-100 min-h-screen">
        <div id="app">
            <nav class="bg-white shadow mb-6">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <div class="flex-shrink-0 flex items-center">
                                <h1 class="text-xl font-bold text-indigo-600">Retail CRM</h1>
                            </div>
                            <div class="ml-10 flex items-center space-x-4">
                                <button @click="backToList()" :class="currentView === 'list' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 hover:text-gray-700'" class="px-3 py-2 rounded-md text-sm font-medium">
                                    Заказы
                                </button>
                                <button @click="currentView = 'create'" :class="currentView === 'create' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 hover:text-gray-700'" class="px-3 py-2 rounded-md text-sm font-medium">
                                    Создать заказ
                                </button>
                                <button @click="currentView = 'movements'" :class="currentView === 'movements' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 hover:text-gray-700'" class="px-3 py-2 rounded-md text-sm font-medium">
                                    Движение товаров
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <orders-list v-if="currentView === 'list'" @edit="viewOrder"></orders-list>
                <order-create v-if="currentView === 'create'" @back="backToList()"></order-create>
                <order-edit v-if="currentView === 'edit'" :order-id="editOrderId" @back="backToList()"></order-edit>
                <stock-movements-list v-if="currentView === 'movements'"></stock-movements-list>
            </main>
        </div>
    </body>
</html>
