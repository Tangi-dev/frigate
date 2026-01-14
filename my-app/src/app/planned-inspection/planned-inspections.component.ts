import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient, HttpClientModule } from '@angular/common/http';
import { lastValueFrom } from 'rxjs';

@Component({
    selector: 'app-planned-inspections',
    standalone: true,
    imports: [CommonModule, FormsModule, HttpClientModule],
    templateUrl: './planned-inspections.component.html',
    styleUrls: ['./planned-inspections.component.scss']
})
export class PlannedInspectionsComponent implements OnInit {
    inspections: any[] = [];
    displayedInspections: any[] = [];

    // Поиск и фильтры
    search = {
        smpName: '',
        controllingAuthority: '',
        status: '',
        startDate: '',
        endDate: ''
    };

    pager: any = {
        currentPage: 1,
        totalPages: 1,
        totalItems: 0,
        perPage: 10
    };

    isLoading = false;
    isImporting = false; // Добавляем флаг для импорта
    errorMessage = '';
    private readonly requestTimeout = 8000;
    // Состояние выбора для множественных операций
    selectedInspections: any[] = [];

    constructor(
        private cdr: ChangeDetectorRef,
        private http: HttpClient,
        private router: Router
    ) {}

    // Вызывал HTTP-Observable с таймаутом, чтоб запросы не висли
    private fetchWithTimeout<T>(observable: any, ms: number = this.requestTimeout): Promise<T> {
        return Promise.race<T>([
            lastValueFrom(observable),
            new Promise<T>((_, reject) => setTimeout(() => reject(new Error('Request timed out')), ms))
        ]);
    }

    async ngOnInit() {
        this.isLoading = true;
        this.errorMessage = '';

        try {
            await this.loadFromApi();
        } catch (err: any) {
            this.errorMessage = err?.message || 'Ошибка при загрузке данных';
        } finally {
            this.isLoading = false;
            try { this.cdr.detectChanges(); } catch (e) { /* noop */}
        }
    }

    private async loadFromApi(): Promise<void> {
        const params: any = {
            page: this.pager.currentPage,
            per_page: this.pager.perPage
        };

        if (this.search.smpName) params.smp_name = this.search.smpName;
        if (this.search.controllingAuthority) params.controlling_authority = this.search.controllingAuthority;
        if (this.search.status) params.status = this.search.status;
        if (this.search.startDate) params.start_date = this.search.startDate;
        if (this.search.endDate) params.end_date = this.search.endDate;

        // Делаю запрос и жду результат/таймаут
        try {
            const response = await this.fetchWithTimeout<any>(
                this.http.get<any>(`/api/inspections`, { params })
            );

            if (!response || response.success !== true) {
                throw new Error(response?.message || 'Неверный формат ответа API');
            }

            this.inspections = response.data || [];
            // Обновил пагинацию
            if (response.pager) {
                this.pager = {
                    currentPage: response.pager.currentPage || 1,
                    totalPages: response.pager.totalPages || 1,
                    totalItems: response.pager.totalItems || 0,
                    perPage: response.pager.perPage || this.pager.perPage
                };
            } else if (response.meta) {
                this.pager = {
                    currentPage: response.meta.current_page || 1,
                    totalPages: response.meta.last_page || 1,
                    totalItems: response.meta.total || (this.inspections || []).length,
                    perPage: response.meta.per_page || this.pager.perPage
                };
            } else {
                this.pager.totalItems = this.inspections.length;
                this.pager.totalPages = Math.max(1, Math.ceil(this.inspections.length / this.pager.perPage));
            }

            // Применяю клиентские фильтры/пагинацию
            this.applyFilters();
        } catch (err: any) {
            console.error('loadFromApi error', err);
            this.errorMessage = err?.message || 'Сетевая ошибка при загрузке данных';
            throw err;
        }
    }

    // Применим фильтры
    private applyFilters() {
        let filtered = [...(this.inspections || [])];

        if (this.search.smpName) {
            const searchTerm = this.search.smpName.toLowerCase();
            filtered = filtered.filter((inspection: any) => (inspection.smp_name || '').toLowerCase().includes(searchTerm));
        }

        if (this.search.controllingAuthority) {
            const searchTerm = this.search.controllingAuthority.toLowerCase();
            filtered = filtered.filter((inspection: any) => (inspection.controlling_authority || '').toLowerCase().includes(searchTerm));
        }

        if (this.search.status) {
            filtered = filtered.filter((inspection: any) => (inspection.status || '') === this.search.status);
        }

        if (this.search.startDate) {
            filtered = filtered.filter((inspection: any) => (inspection.start_date || '') >= this.search.startDate);
        }

        if (this.search.endDate) {
            filtered = filtered.filter((inspection: any) => (inspection.end_date || '') <= this.search.endDate);
        }

        const startIndex = (this.pager.currentPage - 1) * this.pager.perPage;
        const endIndex = startIndex + this.pager.perPage;
        this.displayedInspections = filtered.slice(startIndex, endIndex);
    }

    // Обновим текущую страницу и заново запросим данные с сервера
    changePage(page: number) {
        if (page < 1) return;
        this.pager.currentPage = page;
        this.reload();
    }

    changePerPage() {
        this.pager.currentPage = 1;
        this.reload();
    }

    // Вынес перезагрузку в отдельный метод
    private async reload() {
        this.isLoading = true;
        this.errorMessage = '';
        try {
            await this.loadFromApi();
        } catch (e) {
            console.error('reload error', e);
        } finally {
            this.isLoading = false;
            try { this.cdr.detectChanges(); } catch (e) { /* noop */ }
        }
    }

    clearFilters() {
        this.search = { smpName: '', controllingAuthority: '', status: '', startDate: '', endDate: '' };
        this.pager.currentPage = 1;
        this.reload();
    }

    applyFilter() {
        this.pager.currentPage = 1;
        this.reload();
    }

    refreshData() { this.reload(); }

    addInspection() { this.router.navigate(['/inspections/add']); }
    editInspection(inspection: any) { this.router.navigate(['/inspections/edit', inspection.id]); }
    async deleteInspection(id: number) {
        if (!confirm('Вы уверены, что хотите удалить эту проверку?')) return;
        try {
            const res = await fetch(`/api/inspections/${encodeURIComponent(id)}`, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json' }
            });

            if (!res.ok) {
                const err = await res.json().catch(() => null);
                alert('Ошибка при удалении: ' + (err?.message || res.statusText));
                return;
            }

            // Удаляем из локального состояния
            this.inspections = this.inspections.filter(it => it.id != id);
            this.applyFilters();
            try { this.cdr.detectChanges(); } catch (e) {}
        } catch (e) {
            console.error('delete error', e);
            alert('Сетевая ошибка при удалении');
        }
    }

    // Вспомогательные методы для форматирования
    formatDate(dateString: string): string {
        if (!dateString) return '';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;
        const day = date.getDate().toString().padStart(2, '0');
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const year = date.getFullYear();
        return `${day}.${month}.${year}`;
    }

    getStatusClass(status: string): string {
        switch (status) {
            case 'planned': return 'status-planned';
            case 'in_progress': return 'status-in_progress';
            case 'completed': return 'status-completed';
            case 'cancelled': return 'status-cancelled';
            default: return 'status-planned';
        }
    }

    getStatusText(status: string): string {
        const statusMap: { [key: string]: string } = {
            'planned': 'Запланирована',
            'in_progress': 'В процессе',
            'completed': 'Завершена',
            'cancelled': 'Отменена'
        };
        return statusMap[status] || status;
    }

    getDurationClass(duration: number): string {
        if (duration <= 3) return 'duration-short';
        if (duration <= 7) return 'duration-medium';
        return 'duration-long';
    }

    getDaysText(days: number): string {
        const lastDigit = days % 10;
        const lastTwoDigits = days % 100;
        if (lastTwoDigits >= 11 && lastTwoDigits <= 19) return 'дней';
        switch (lastDigit) {
            case 1: return 'день';
            case 2:
            case 3:
            case 4: return 'дня';
            default: return 'дней';
        }
    }

    importExcel() {
        // Создаем input для выбора файла
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.xlsx,.xls,.csv';
        input.style.display = 'none';

        input.onchange = (event: any) => {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('excel_file', file);
            formData.append('update_existing', 'true');

            // Показываем индикатор загрузки
            this.isImporting = true;
            this.cdr.detectChanges();

            // Исправленный маршрут для импорта
            this.http.post('/api/inspections/import', formData) // Изменено с /api/planned-inspections/import
                .subscribe({
                    next: (response: any) => {
                        this.isImporting = false;

                        if (response.success) {
                            alert(response.message);
                            // Обновляем данные на странице
                            this.reload();
                        } else {
                            alert('Ошибка импорта: ' + response.message);

                            // Показываем ошибки подробно, если есть
                            if (response.data?.errors?.length > 0) {
                                const errors = response.data.errors.slice(0, 5).join('\n');
                                alert('Первые 5 ошибок:\n' + errors);
                            }
                        }
                        this.cdr.detectChanges();
                    },
                    error: (error) => {
                        this.isImporting = false;
                        alert('Ошибка при импорте файла: ' + error.message);
                        console.error('Import error:', error);
                        this.cdr.detectChanges();
                    }
                });
        };

        document.body.appendChild(input);
        input.click();
        document.body.removeChild(input);
    }

    async exportToExcel() {
        try {
            // Собираем параметры
            const params: {[key: string]: string} = {
                smp_name: this.search.smpName,
                controlling_authority: this.search.controllingAuthority,
                status: this.search.status,
                start_date: this.search.startDate,
                end_date: this.search.endDate
            };

            // Очищаем пустые параметры
            const cleanParams: {[key: string]: string} = {};
            Object.keys(params).forEach(key => {
                if (params[key]) {
                    cleanParams[key] = params[key];
                }
            });

            // Формируем URL
            let url = '/api/inspections/export';
            const queryString = new URLSearchParams(cleanParams).toString();
            if (queryString) {
                url += '?' + queryString;
            }

            // Используем fetch для лучшего контроля
            const response = await fetch(url);

            if (!response.ok) {
                const error = await response.json().catch(() => null);
                throw new Error(error?.message || `HTTP error! status: ${response.status}`);
            }

            // Получаем blob и создаем ссылку для скачивания
            const blob = await response.blob();
            const downloadUrl = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = `planned_inspections_${new Date().toISOString().slice(0,10)}.xlsx`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            // Освобождаем память
            window.URL.revokeObjectURL(downloadUrl);

        } catch (error: any) {
            console.error('Export error:', error);
            alert('Ошибка при экспорте: ' + (error.message || 'Неизвестная ошибка'));
        }
    }

// Для шаблона тоже используем fetch
    async downloadTemplate() {
        try {
            const response = await fetch('/api/inspections/download-template');

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const blob = await response.blob();
            const downloadUrl = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = 'template_import_inspections.xlsx';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            window.URL.revokeObjectURL(downloadUrl);

        } catch (error: any) {
            console.error('Download template error:', error);
            alert('Ошибка при скачивании шаблона: ' + (error.message || 'Неизвестная ошибка'));
        }
    }
}