{{-- PhotoLightbox (§11.2): zoom, rotate, navigasi, unduh. Dipicu event
     window 'open-photo-lightbox' dengan detail { images: [url...], index }. --}}
<div x-data="{
    open: false,
    images: [],
    index: 0,
    scale: 1,
    rotation: 0,
    get current() { return this.images[this.index] || ''; },
    show(detail) {
        this.images = detail.images || [];
        this.index = detail.index || 0;
        this.reset();
        this.open = true;
        document.body.style.overflow = 'hidden';
    },
    close() { this.open = false; document.body.style.overflow = ''; },
    reset() { this.scale = 1; this.rotation = 0; },
    next() { if (this.index < this.images.length - 1) { this.index++; this.reset(); } },
    prev() { if (this.index > 0) { this.index--; this.reset(); } },
    zoomIn() { this.scale = Math.min(this.scale + 0.25, 5); },
    zoomOut() { this.scale = Math.max(this.scale - 0.25, 0.5); },
    rotate() { this.rotation = (this.rotation + 90) % 360; },
    download() {
        const a = document.createElement('a');
        a.href = this.current; a.download = 'nota-foto-' + (this.index + 1);
        document.body.appendChild(a); a.click(); a.remove();
    }
}" @open-photo-lightbox.window="show($event.detail)" @keydown.escape.window="close()"
    @keydown.arrow-right.window="next()" @keydown.arrow-left.window="prev()">

    <template x-teleport="body">
        <div x-show="open" x-transition.opacity class="fixed inset-0 z-[9999] flex flex-col" style="display:none;">
            <div class="absolute inset-0 bg-black/80 backdrop-blur-xl" @click="close()"></div>

            {{-- Toolbar --}}
            <div class="relative z-10 flex justify-between items-center p-4 text-white">
                <span class="text-sm font-mono bg-black/40 px-3 py-1 rounded-full">
                    <span x-text="index + 1"></span> / <span x-text="images.length"></span>
                </span>
                <div class="flex items-center gap-2">
                    <button @click="zoomOut()" class="p-2 bg-black/40 hover:bg-white/20 rounded-full" title="Zoom out">
                        <x-icon name="o-magnifying-glass-minus" class="w-5 h-5" /></button>
                    <button @click="zoomIn()" class="p-2 bg-black/40 hover:bg-white/20 rounded-full" title="Zoom in">
                        <x-icon name="o-magnifying-glass-plus" class="w-5 h-5" /></button>
                    <button @click="rotate()" class="p-2 bg-black/40 hover:bg-white/20 rounded-full" title="Rotate">
                        <x-icon name="o-arrow-path" class="w-5 h-5" /></button>
                    <button @click="download()" class="p-2 bg-black/40 hover:bg-white/20 rounded-full" title="Download">
                        <x-icon name="o-arrow-down-tray" class="w-5 h-5" /></button>
                    <button @click="close()" class="p-2 bg-black/40 hover:bg-white/20 rounded-full" title="Close">
                        <x-icon name="o-x-mark" class="w-5 h-5" /></button>
                </div>
            </div>

            {{-- Image --}}
            <div class="relative z-10 flex-1 flex items-center justify-center p-4 overflow-hidden"
                @wheel.prevent="$event.deltaY < 0 ? zoomIn() : zoomOut()">
                <button @click.stop="prev()" x-show="images.length > 1"
                    class="absolute left-4 p-3 rounded-full bg-black/40 hover:bg-white/20 text-white z-20">
                    <x-icon name="o-chevron-left" class="w-7 h-7" /></button>

                <img :src="current" x-show="current !== ''"
                    :style="`transform: scale(${scale}) rotate(${rotation}deg);`"
                    class="max-w-full max-h-full object-contain transition-transform duration-200 select-none" />

                <button @click.stop="next()" x-show="images.length > 1"
                    class="absolute right-4 p-3 rounded-full bg-black/40 hover:bg-white/20 text-white z-20">
                    <x-icon name="o-chevron-right" class="w-7 h-7" /></button>
            </div>

            {{-- Thumbnails --}}
            <div x-show="images.length > 1" class="relative z-10 flex justify-center gap-2 p-4 overflow-x-auto">
                <template x-for="(img, i) in images" :key="i">
                    <img :src="img" @click="index = i; reset()"
                        class="w-14 h-14 object-cover rounded-lg cursor-pointer border-2 transition"
                        :class="index === i ? 'border-primary opacity-100' : 'border-transparent opacity-50 hover:opacity-100'" />
                </template>
            </div>
        </div>
    </template>
</div>
