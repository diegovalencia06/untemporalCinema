<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
  movie: { type: Object, required: true }
});

const capitalizar = (str) => str.charAt(0).toUpperCase() + str.slice(1);

const proximos7Dias = computed(() => {
  const dias = [];
  const hoy = new Date();
  for (let i = 0; i < 7; i++) {
    const f = new Date();
    f.setDate(hoy.getDate() + i);
    const year = f.getFullYear();
    const month = String(f.getMonth() + 1).padStart(2, '0');
    const day = String(f.getDate()).padStart(2, '0');
    const fechaISO = `${year}-${month}-${day}`;
    let label = i === 0 ? 'Hoy' : f.toLocaleDateString('es-ES', { weekday: 'short' }).replace('.', '');
    dias.push({ label: capitalizar(label), numero: f.getDate().toString(), fecha_completa: fechaISO });
  }
  return dias;
});

const fechaSeleccionada = ref(proximos7Dias.value[0].fecha_completa);

const sesionesFiltradas = computed(() => {
  if (!props.movie?.sessions) return [];
  
  const ahora = new Date(); 

  return props.movie.sessions.filter(s => {
    const inicioSesion = new Date(s.start_time);
    const fechaSesion = s.start_time.split(' ')[0].split('T')[0];
    
    return fechaSesion === fechaSeleccionada.value && inicioSesion > ahora;
  });
});

const formatearHora = (t) => new Date(t).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', hour12: false });
</script>

<template>
  <div class="bg-surface-dim text-on-surface font-body selection:bg-primary/30 min-h-screen pb-12">
    
    <header class="bg-[#0c1324]/70 backdrop-blur-xl font-headline fixed top-0 w-full z-50 border-b border-white/10 shadow-[0_8px_30px_rgb(220,38,38,0.1)]">
      <div class="flex justify-between items-center w-full px-6 py-4 max-w-screen-2xl mx-auto">
        <Link href="/" class="text-2xl font-black tracking-tighter bg-gradient-to-r from-orange-500 to-red-600 bg-clip-text text-transparent">
            LUMINOUS
        </Link>
        
        <nav class="hidden md:flex items-center space-x-8 font-body">
          <Link class="text-on-surface-variant hover:text-on-surface transition-colors font-bold" href="/">Cartelera</Link>
          <a class="text-on-surface-variant hover:text-on-surface transition-colors" href="#">Ofertas</a>
        </nav>
        
        <div class="flex items-center space-x-4">
          <button class="p-2 hover:bg-white/5 transition-all duration-300 rounded-full active:scale-95">
            <span class="material-symbols-outlined text-on-surface">search</span>
          </button>
          
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

    <main v-if="movie" class="pt-20">
      
      <section class="relative h-[60vh] md:h-[70vh] flex items-end overflow-hidden">
        <div class="absolute inset-0 z-0 bg-surface-container-highest">
          <img v-if="movie.backdrop_path || movie.poster_path" :src="`https://image.tmdb.org/t/p/original${movie.backdrop_path ?? movie.poster_path}`" class="w-full h-full object-cover brightness-[0.3] blur-[1px]" />
          <div class="absolute inset-0 bg-gradient-to-t from-surface-dim via-surface-dim/40 to-transparent"></div>
        </div>

        <div class="relative z-10 max-w-screen-2xl mx-auto w-full px-6 lg:px-12 pb-12 flex flex-col md:flex-row gap-10 items-center md:items-end">
          <div class="hidden md:block w-64 shrink-0 rounded-2xl overflow-hidden shadow-[0_10px_40px_rgba(0,0,0,0.5)] border border-white/10 bg-surface-container">
            <img v-if="movie.poster_path" :src="`https://image.tmdb.org/t/p/w500${movie.poster_path}`" class="w-full aspect-[2/3] object-cover" />
          </div>
          <div class="flex-1 text-left space-y-6">
            <div class="flex items-center gap-3 text-primary font-black tracking-widest text-xs uppercase">
              <template v-if="movie.generos" v-for="(genero, index) in movie.generos.split(',')" :key="index">
                <span>{{ genero.trim() }}</span>
                <span 
                  v-if="index !== movie.generos.split(',').length - 1" 
                  class="w-1 h-1 rounded-full bg-on-surface-variant opacity-50"
                ></span>
              </template>
              <span v-else>SIN GÉNERO</span>
            </div>
            <h1 class="text-5xl lg:text-7xl font-black font-headline tracking-tighter leading-none uppercase italic text-on-surface">{{ movie.title }}</h1>
            
            <button class="bg-gradient-to-r from-orange-500 to-red-600 text-white px-8 py-3 rounded-xl font-black uppercase tracking-tighter transition-all flex items-center gap-2 active:scale-95 shadow-[0_8px_30px_rgb(220,38,38,0.3)] hover:shadow-[0_10px_40px_rgb(220,38,38,0.4)]">
              <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">play_arrow</span>
              Ver Tráiler
            </button>
          </div>
        </div>
      </section>

      <div class="max-w-screen-2xl mx-auto px-6 lg:px-12 py-16">
        <div class="flex flex-col lg:flex-row gap-16">
          
          <div class="flex-1 space-y-12 text-left">
            <div class="space-y-4">
              <h2 class="text-2xl font-black font-headline uppercase tracking-tighter border-l-4 border-primary pl-4 text-on-surface">Sinopsis</h2>
              <p class="text-lg text-on-surface-variant leading-relaxed font-medium">
                {{ movie.synopsis }}
              </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="bg-surface-container-low p-6 rounded-2xl border border-white/5 transition-colors hover:bg-surface-container">
                <p class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant opacity-70 mb-1">Director</p>
                <p class="text-lg font-bold font-headline text-on-surface">{{ movie.director }}</p>
              </div>
              <div class="bg-surface-container-low p-6 rounded-2xl border border-white/5 transition-colors hover:bg-surface-container">
                <p class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant opacity-70 mb-1">Estudio</p>
                <p class="text-lg font-bold font-headline text-on-surface">{{ movie.productora ? movie.productora.split(',')[0] : 'Sin estudio' }}</p>
              </div>
            </div>
          </div>

          <div class="w-full lg:w-[400px] shrink-0">
            <div class="bg-surface-container-low border border-white/5 p-8 rounded-[2.5rem] shadow-2xl space-y-8 text-left">
              <h3 class="text-2xl font-black font-headline uppercase tracking-tighter italic text-on-surface">Entradas</h3>
              
              <div class="w-full">
                <p class="text-[10px] font-black uppercase text-on-surface-variant opacity-70 mb-3 text-center">Elige el día</p>
                
                <div class="flex justify-start gap-3 overflow-x-auto py-3 px-2 snap-x snap-mandatory
                            [&::-webkit-scrollbar]:h-1.5 
                            [&::-webkit-scrollbar-track]:bg-white/5 [&::-webkit-scrollbar-track]:rounded-full 
                            [&::-webkit-scrollbar-thumb]:bg-white/10 [&::-webkit-scrollbar-thumb]:rounded-full 
                            hover:[&::-webkit-scrollbar-thumb]:bg-primary pb-4">
                            
                  <button v-for="dia in proximos7Dias" :key="dia.fecha_completa"
                    @click="fechaSeleccionada = dia.fecha_completa"
                    :class="[
                      'flex-none snap-center flex flex-col items-center justify-center w-14 h-20 rounded-xl transition-all duration-300 relative', 
                      fechaSeleccionada === dia.fecha_completa 
                        ? 'bg-primary-container text-on-primary-container shadow-[0_10px_30px_rgba(220,38,38,0.4)] scale-110 z-10 ring-2 ring-primary/30' 
                        : 'bg-surface-container text-on-surface-variant border border-white/5 hover:bg-surface-container-highest z-0 hover:z-10'
                    ]">
                    <span class="text-[10px] font-bold uppercase tracking-widest opacity-80 mb-1">{{ dia.label }}</span>
                    <span class="text-xl font-black font-headline">{{ dia.numero }}</span>
                  </button>
                  
                </div>
              </div>

              <div class="space-y-4">
                <p class="text-[10px] font-black uppercase text-on-surface-variant opacity-70">Sesiones disponibles</p>
                <div v-if="sesionesFiltradas.length > 0" class="grid grid-cols-2 gap-3">
                  <Link v-for="session in sesionesFiltradas" :key="session.id" :href="`/sesion/${session.id}/asientos`"
                    class="bg-surface-container text-on-surface border border-white/10 hover:border-primary hover:bg-primary/10 p-4 rounded-xl text-center transition-all group active:scale-95">
                    <span class="block text-lg font-black font-headline group-hover:text-primary transition-colors">{{ formatearHora(session.start_time) }}</span>
                    <span class="text-[10px] text-on-surface-variant opacity-70 uppercase font-bold mt-1 block">{{  session.room?.name }}</span>
                  </Link>
                </div>
                <div v-else class="py-10 text-center bg-surface-container-highest/30 rounded-2xl border border-dashed border-white/5 italic text-on-surface-variant">
                  No hay sesiones para hoy.
                </div>
              </div>

              
            </div>
          </div>

        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
/* Scoped styles eliminados de no-scrollbar ya que ahora se usan las pseudoclases de webkit en el HTML */
</style>