<script setup>
import { ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';

// --- LÓGICA DEL BUSCADOR ---
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
</script>

<template>
    <div class="bg-surface-dim text-on-surface font-body selection:bg-primary/30 min-h-screen pb-12">
        
        <header class="bg-[#0c1324]/70 backdrop-blur-xl font-headline fixed top-0 w-full z-50 border-b border-white/10 shadow-[0_8px_30px_rgb(220,38,38,0.1)]">
            <div class="flex justify-between items-center w-full px-6 py-4 max-w-screen-2xl mx-auto">
                
                <Link href="/" class="text-2xl font-black tracking-tighter bg-gradient-to-r from-orange-500 to-red-600 bg-clip-text text-transparent">
                    LUMINOUS
                </Link>
                
                <nav class="hidden md:flex items-center space-x-8 font-body">
                    <Link 
                        href="/" 
                        :class="[
                            'transition-colors font-bold',
                            $page.url === '/' 
                                ? 'text-red-500 underline decoration-2 underline-offset-8' 
                                : 'text-on-surface-variant hover:text-on-surface'
                        ]"
                    >
                        Cartelera
                    </Link>
                    
                    <Link 
                        href="/ofertas" 
                        :class="[
                            'transition-colors font-bold',
                            $page.url.startsWith('/ofertas') 
                                ? 'text-red-500 underline decoration-2 underline-offset-8' 
                                : 'text-on-surface-variant hover:text-on-surface'
                        ]"
                    >
                        Ofertas
                    </Link>
                </nav>
                
                <div class="flex items-center space-x-4">
                    <div class="relative group">
                        <div class="flex items-center bg-white/5 border border-white/10 rounded-full px-4 py-1.5 focus-within:border-red-500/50 transition-all w-48 md:w-64">
                            <span class="material-symbols-outlined text-slate-400 text-sm mr-2">search</span>
                            
                            <input 
                                v-model="searchQuery"
                                type="text" 
                                placeholder="Busca tu película..." 
                                class="bg-transparent border-none outline-none focus:outline-none focus:ring-0 text-xs text-white placeholder:text-slate-500 w-full p-0"
                                @focus="abrirBuscador"
                                @blur="cerrarBuscador"
                            />
                        </div>

                        <div v-if="isFocused && results.length > 0" class="absolute top-full mt-2 w-full bg-[#191f31] border border-white/10 rounded-xl shadow-2xl overflow-hidden z-[100]">
                            <Link 
                                v-for="movie in results" 
                                :key="movie.id"
                                :href="`/pelicula/${movie.id}`" 
                                class="flex items-center gap-3 p-3 hover:bg-white/5 transition-colors border-b border-white/5 last:border-none"
                                @click="searchQuery = ''; results = []"
                            >
                                <img :src="`https://image.tmdb.org/t/p/w92${movie.poster_path}`" class="w-8 h-12 object-cover rounded shadow" />
                                <div>
                                    <p class="text-xs font-bold text-white leading-tight">{{ movie.title }}</p>
                                </div>
                            </Link>
                        </div>
                    </div>

                    <Link v-if="!$page.props.auth.user" href="/login" class="p-2 hover:bg-white/5 transition-all duration-300 rounded-full active:scale-95 flex items-center gap-2 text-on-surface-variant hover:text-white">
                        <span class="text-sm font-bold hidden md:block">Entrar</span>
                        <span class="material-symbols-outlined text-on-surface">account_circle</span>
                    </Link>

                    <Link v-else href="/perfil" class="p-2 bg-white/5 hover:bg-white/10 transition-all duration-300 rounded-full active:scale-95 flex items-center gap-2 border border-white/10">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-red-500 to-orange-500 flex items-center justify-center text-white font-bold text-sm">
                            {{ $page.props.auth.user.name.charAt(0) }}
                        </div>
                        <span class="text-sm font-bold hidden md:block text-white">{{ $page.props.auth.user.name }}</span>
                    </Link>
                </div>
            </div>
        </header>

        <main>
            <slot />
        </main>
        
    </div>
</template>