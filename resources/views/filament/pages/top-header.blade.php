<x-filament-panels::page>
    <div class="space-y-6" x-data="topHeaderSorter()" x-init="initSorter()">
        <div class="rounded-xl border border-white/10 bg-slate-900/50 p-4 shadow-sm backdrop-blur">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-white">Masaüstü Üst Menü (header_primary)</h3>
                    <p class="text-xs text-slate-400">Bu ekrandan yalnızca public masaüstü üst menüsünü tek bir yerden düzenleyebilirsiniz. Sıralamayı değiştirmek için sürükleme tutamacını kullanabilirsiniz.</p>
                </div>
            </div>
        </div>

        {{ $this->table }}
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('topHeaderSorter', () => ({
                sortable: null,
                isSaving: false,

                initSorter() {
                    this.$nextTick(() => {
                        this.bindSortable();
                    });

                    if (window.Livewire) {
                        Livewire.hook('commit', ({ succeed }) => {
                            succeed(() => {
                                this.$nextTick(() => this.bindSortable());
                            });
                        });
                    }
                },

                bindSortable() {
                    const tbody = this.$el.querySelector('tbody');
                    if (!tbody) return;

                    const allRows = Array.from(tbody.querySelectorAll('tr'));
                    allRows.forEach((row) => {
                        const handle = row.querySelector('.drag-handle');
                        if (handle) {
                            row.setAttribute('data-id', handle.getAttribute('data-id'));
                            row.setAttribute('data-parent-id', handle.getAttribute('data-parent-id') || 'root');
                        }
                    });

                    if (this.sortable) {
                        this.sortable.destroy();
                    }

                    this.sortable = new Sortable(tbody, {
                        handle: '.drag-handle',
                        animation: 150,
                        ghostClass: 'bg-rose-500/10',
                        chosenClass: 'bg-slate-100/50',
                        dragClass: 'opacity-50',
                        touchStartThreshold: 5,

                        onMove: (evt) => {
                            const draggedParent = evt.dragged.getAttribute('data-parent-id');
                            const targetParent = evt.related.getAttribute('data-parent-id');
                            return draggedParent === targetParent;
                        },

                        onEnd: (evt) => {
                            if (evt.oldIndex === evt.newIndex) return;
                            if (this.isSaving) return;

                            const rows = Array.from(tbody.querySelectorAll('tr[data-id]'));
                            const items = [];
                            const parentCounters = {};

                            rows.forEach((row) => {
                                const id = parseInt(row.getAttribute('data-id'));
                                const parentId = row.getAttribute('data-parent-id') || 'root';

                                if (isNaN(id)) return;

                                if (!parentCounters[parentId]) {
                                    parentCounters[parentId] = 0;
                                }

                                items.push({
                                    id: id,
                                    position: parentCounters[parentId]++,
                                });
                            });

                            this.saveOrder(items, evt);
                        }
                    });
                },

                async saveOrder(items, evt) {
                    if (!items.length) return;
                    this.isSaving = true;

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                    try {
                        const response = await fetch('/admin/menu-items/reorder', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({ items: items }),
                        });

                        const result = await response.json();

                        if (response.ok && result.success) {
                            if (window.Livewire) {
                                window.dispatchEvent(new CustomEvent('notify', {
                                    detail: {
                                        title: result.message || 'Top Header sıralaması başarıyla kaydedildi.',
                                        status: 'success'
                                    }
                                }));
                            }
                        } else {
                            throw new Error(result.message || 'Sıralama kaydedilirken bir hata oluştu.');
                        }
                    } catch (error) {
                        if (evt && evt.from) {
                            const item = evt.item;
                            const reference = evt.oldIndex < evt.newIndex 
                                ? evt.from.children[evt.oldIndex] 
                                : evt.from.children[evt.oldIndex + 1];
                            if (reference) {
                                evt.from.insertBefore(item, reference);
                            }
                        }

                        if (window.Livewire) {
                            window.dispatchEvent(new CustomEvent('notify', {
                                detail: {
                                    title: error.message || 'Sıralama kaydedilemedi.',
                                    status: 'danger'
                                }
                            }));
                        }
                    } finally {
                        this.isSaving = false;
                    }
                }
            }));
        });
    </script>
</x-filament-panels::page>
