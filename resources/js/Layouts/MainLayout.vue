<script setup>
import { ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';

const searchQuery = ref('');
const results = ref([]);
const isFocused = ref(false);

const abrirBuscador = () => {
    isFocused.value = true;
};

const cerrarBuscador = () => {
    setTimeout(() => {
        isFocused.value = false;
    }, 200);
};

watch(searchQuery, async (newQuery) => {
    if (newQuery.length < 2) {
        results.value = [];
        return;
    }
    try {
        const response = await axios.get(`/buscar-peliculas?q=${newQuery}`);
        results.value = response.data;
    } catch (error) {
        console.error("Error en la búsqueda:", error);
    }
});

const isDropdownOpen = ref(false); 
</script>

<template>
    <div class="bg-surface-dim text-on-surface font-body selection:bg-primary/30 min-h-screen pb-12">
        
        <header class="bg-[#0c1324]/70 backdrop-blur-xl font-headline fixed top-0 w-full z-50 border-b border-white/10 shadow-[0_8px_30px_rgb(220,38,38,0.1)]">
            <div class="flex justify-between items-center w-full px-4 md:px-6 py-3 md:py-4 max-w-screen-2xl mx-auto gap-2 md:gap-4">
                
                <Link href="/" class="text-lg md:text-2xl font-black tracking-tighter bg-gradient-to-r from-orange-500 to-red-600 bg-clip-text text-transparent truncate shrink-0">
                    <span class="hidden sm:inline">Untemporal Cinema</span>
                    <span class="sm:hidden">UCinema</span>
                </Link>
                
                <div class="flex items-center space-x-2 md:space-x-4">
                    
                    <div class="relative group">
                        <div class="flex items-center bg-white/5 border border-white/10 rounded-full px-3 md:px-4 py-1.5 md:py-2 focus-within:border-red-500/50 transition-all w-28 sm:w-48 md:w-64">
                            <span class="material-symbols-outlined text-slate-400 text-[16px] md:text-sm mr-1.5 md:mr-2">search</span>
                            
                            <input 
                                v-model="searchQuery"
                                type="text" 
                                placeholder="Buscar..." 
                                class="bg-transparent border-none outline-none focus:outline-none focus:ring-0 text-[10px] md:text-xs text-white placeholder:text-slate-500 w-full p-0 truncate"
                                @focus="abrirBuscador"
                                @blur="cerrarBuscador"
                            />
                        </div>

                        <div v-if="isFocused && results.length > 0" class="absolute top-full mt-2 w-[250px] sm:w-full right-0 sm:right-auto bg-[#191f31] border border-white/10 rounded-xl shadow-2xl overflow-hidden z-[100]">
                            <Link 
                                v-for="movie in results" 
                                :key="movie.id"
                                :href="`/pelicula/${movie.id}`" 
                                class="flex items-center gap-3 p-3 hover:bg-white/5 transition-colors border-b border-white/5 last:border-none"
                                @click="searchQuery = ''; results = []"
                            >
                                <img :src="`https://image.tmdb.org/t/p/w92${movie.poster_path}`" class="w-8 h-12 object-cover rounded shadow shrink-0" />
                                <div class="truncate">
                                    <p class="text-xs font-bold text-white leading-tight truncate">{{ movie.title }}</p>
                                </div>
                            </Link>
                        </div>
                    </div>

                    <Link v-if="!$page.props.auth.user" href="/login" class="p-1.5 md:p-2 hover:bg-white/5 transition-all duration-300 rounded-full active:scale-95 flex items-center gap-2 text-on-surface-variant hover:text-white shrink-0">
                        <span class="text-sm font-bold hidden md:block">Entrar</span>
                        <span class="material-symbols-outlined text-[20px] md:text-[24px] text-on-surface">account_circle</span>
                    </Link>

                    <div v-else class="relative shrink-0">
                        <button 
                            @click="isDropdownOpen = !isDropdownOpen" 
                            class="p-1 md:pr-4 bg-white/5 hover:bg-white/10 transition-all duration-300 rounded-full active:scale-95 flex items-center gap-2 md:gap-3 border border-white/10 outline-none"
                        >
                            <div class="w-7 h-7 md:w-8 md:h-8 rounded-full bg-gradient-to-tr from-red-500 to-orange-500 flex items-center justify-center text-white font-bold text-xs md:text-sm uppercase shrink-0">
                                {{ $page.props.auth.user.name.charAt(0) }}
                            </div>
                            <span class="text-sm font-bold hidden md:block text-white truncate max-w-[100px]">
                                {{ $page.props.auth.user.name }}
                            </span>
                        </button>

                        <div 
                            v-if="isDropdownOpen" 
                            @click="isDropdownOpen = false" 
                            class="fixed inset-0 z-40"
                        ></div>

                        <transition
                            enter-active-class="transition ease-out duration-200"
                            enter-from-class="transform opacity-0 scale-95 translate-y-2"
                            enter-to-class="transform opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition ease-in duration-150"
                            leave-from-class="transform opacity-100 scale-100 translate-y-0"
                            leave-to-class="transform opacity-0 scale-95 translate-y-2"
                        >
                            <div 
                                v-if="isDropdownOpen" 
                                class="absolute right-0 mt-3 w-52 md:w-56 bg-[#191f31] border border-white/10 rounded-2xl shadow-2xl py-2 z-50 overflow-hidden"
                            >
                                <Link 
                                    href="/mi-perfil" 
                                    @click="isDropdownOpen = false"
                                    class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:text-white hover:bg-white/5 transition-colors"
                                >
                                    <span class="material-symbols-outlined text-[18px] md:text-[20px]">person</span>
                                    <span class="text-xs md:text-sm font-medium">Mi Perfil</span>
                                </Link>

                                <a 
                                    v-if="$page.props.auth.user.is_admin"
                                    href="/admin" 
                                    @click="isDropdownOpen = false"
                                    class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:text-white hover:bg-white/5 transition-colors"
                                >
                                    <span class="material-symbols-outlined text-[18px] md:text-[20px]">settings</span>
                                    <span class="text-xs md:text-sm font-medium">Panel Admin</span>
                                </a>

                                <div class="h-px bg-white/10 my-1"></div>

                                <Link 
                                    href="/logout" 
                                    method="post" 
                                    as="button"
                                    @click="isDropdownOpen = false"
                                    class="w-full flex items-center gap-3 px-4 py-3 text-red-400 hover:text-red-300 hover:bg-red-500/10 transition-colors text-left"
                                >
                                    <span class="material-symbols-outlined text-[18px] md:text-[20px]">logout</span>
                                    <span class="text-xs md:text-sm font-medium">Cerrar sesión</span>
                                </Link>
                            </div>
                        </transition>
                    </div>

                </div>
            </div>
        </header>

        <main>
            <slot />
        </main>
        
    </div>
</template>