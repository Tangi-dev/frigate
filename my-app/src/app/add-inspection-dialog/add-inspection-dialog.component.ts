import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router, ActivatedRoute } from '@angular/router';

@Component({
    selector: 'app-add-inspection',
    standalone: true,
    imports: [
        CommonModule,
        ReactiveFormsModule
    ],
    templateUrl: './add-inspection-dialog.component.html',
    styleUrls: ['./add-inspection-dialog.component.scss']
})
export class AddInspectionComponent implements OnInit, OnDestroy {
    inspectionForm: FormGroup;
    isEditMode = false;
    dateRangeError = false;

    // Контролирующие органы: загружаются из БД через API (из поля controlling_authority таблицы planned_inspections)
    controllingAuthorities: string[] = [];
    contAuthLoading = false; // флаг загрузки контролирующих органов
    contAuthLoadError = false; // флаг ошибки загрузки контролирующих органов
    contAuthLoaded = false; // флаг, что органы уже загружены

    // Результаты поиска СМП
    smpResults: Array<{ id: number | string, name: string, inn?: string }> = [];
    private fullSmpList: Array<{ id: number | string, name: string, inn?: string }> = [];
    private searchTimer: any = null;
    smpLoaded = false;
    smpLoading = false;
    isSaving = false;
    serverErrors: any = null;

    constructor(
        private fb: FormBuilder,
        private router: Router,
        private route: ActivatedRoute
    ) {
        this.inspectionForm = this.fb.group({
            inspectionId: [null],
            smpId: [null, Validators.required],
            smpText: [''],
            controllingAuthority: ['', Validators.required],
            startDate: ['', Validators.required],
            endDate: ['', Validators.required],
            plannedDuration: [{ value: '', disabled: true }, [Validators.required, Validators.min(1)]],
            status: ['planned'],
            notes: ['']
        });
    }

    ngOnInit(): void {
        this.route.queryParams.subscribe(params => {
            this.isEditMode = params['edit'] === 'true' || params['id'] != null;
            if (params['data']) {
                try {
                    const data = JSON.parse(params['data']);
                    this.patchFormForAdd(data);
                } catch {}
            } else if (params['id']) {
                const id = params['id'];
                this.loadInspectionForEdit(id);
            }
        });

        // Поддержка route params
        this.route.paramMap.subscribe(paramMap => {
            const id = paramMap.get('id');
            if (id) {
                this.isEditMode = true;
                this.loadInspectionForEdit(id);
            }
        });

        // Подписки для пересчёта длительности
        this.inspectionForm.get('startDate')?.valueChanges.subscribe(() => this.calculateDuration());
        this.inspectionForm.get('endDate')?.valueChanges.subscribe(() => this.calculateDuration());
    }

    ngOnDestroy(): void {
        if (this.searchTimer) {
            clearTimeout(this.searchTimer);
        }
    }

    // Загрузка контролирующих органов при фокусе на поле
    loadControllingAuthorities(): void {
        if (this.contAuthLoaded || this.contAuthLoading) return;
        this.loadControllingAuthoritiesFromApi();
    }

    private async loadControllingAuthoritiesFromApi(): Promise<void> {
        this.contAuthLoading = true;
        this.contAuthLoadError = false;

        try {
            const res = await fetch(`/api/controlling-authorities`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!res.ok) {
                throw new Error(`HTTP ${res.status}`);
            }

            const data = await res.json();
            const items = Array.isArray(data) ? data : data.data || data.results || [];

            this.controllingAuthorities = items
                .map((it: any) => typeof it === 'string' ? it : it.controlling_authority || it.name || String(it))
                .filter(Boolean)
                .sort();

            this.contAuthLoadError = this.controllingAuthorities.length === 0;
        } catch (e) {
            console.error('error: ', e);
            this.controllingAuthorities = [];
            this.contAuthLoadError = true;
        } finally {
            this.contAuthLoading = false;
            this.contAuthLoaded = true;
        }
    }

    // Для поиска СМП
    onSmpInput(value: string): void {
        // Сбрасываем выбранный smpId при ручном изменении текста
        this.inspectionForm.patchValue({ smpId: null });
        if (this.searchTimer) clearTimeout(this.searchTimer);
        this.searchTimer = setTimeout(() => {
            this.performSmpSearch(value);
        }, 400);
    }

    private async performSmpSearch(q: string): Promise<void> {
        const trimmed = (q || '').trim();
        if (this.fullSmpList.length > 0) {
            if (!trimmed || trimmed.length < 2) {
                // Показываем первоначальную выборку
                this.smpResults = this.fullSmpList.slice(0, 50);
                return;
            }
            const lower = trimmed.toLowerCase();
            this.smpResults = this.fullSmpList.filter(it => (
                (it.name || '').toLowerCase().includes(lower) ||
                (it.inn || '').toString().includes(lower)
            )).slice(0, 50);
            // Если в локальном кэше нет совпадений — делаем серверный поиск
            if (this.smpResults.length > 0) return;
        } else {
            if (!trimmed || trimmed.length < 2) {
                this.smpResults = [];
                return;
            }
        }

        // Серверный поиск
        try {
            const url = `/api/smp/search?q=${encodeURIComponent(trimmed)}`;
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) {
                this.smpResults = [];
                return;
            }
            const json = await res.json();
            let items: any[] = [];
            if (Array.isArray(json)) items = json;
            else if (Array.isArray(json.results)) items = json.results;
            else if (Array.isArray(json.data)) items = json.data;

            this.smpResults = items.map((it: any) => ({
                id: it.id,
                name: it.name || it.text || '',
                inn: it.inn
            }));
        } catch (e) {
            console.error('error: ', e);
            this.smpResults = [];
        }
    }

    selectSmp(s: { id: number | string, name: string, inn?: string }): void {
        this.inspectionForm.patchValue({ smpId: s.id, smpText: s.name });
        this.smpResults = [];
    }

    calculateDuration(): void {
        const start = this.inspectionForm.get('startDate')?.value;
        const end = this.inspectionForm.get('endDate')?.value;
        this.dateRangeError = false;

        if (start && end) {
            const s = new Date(start);
            const e = new Date(end);
            if (e < s) {
                this.dateRangeError = true;
                this.inspectionForm.patchValue({ plannedDuration: '' });
                return;
            }
            const diffTime = e.getTime() - s.getTime();
            const diffDays = Math.max(1, Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1);
            this.inspectionForm.get('plannedDuration')?.enable();
            this.inspectionForm.patchValue({ plannedDuration: diffDays });
            this.inspectionForm.get('plannedDuration')?.disable();
        } else {
            this.inspectionForm.get('plannedDuration')?.enable();
            this.inspectionForm.patchValue({ plannedDuration: '' });
            this.inspectionForm.get('plannedDuration')?.disable();
        }
    }

    async onSave(): Promise<void> {
        if (this.inspectionForm.invalid || this.dateRangeError) {
            this.inspectionForm.markAllAsTouched();
            return;
        }

        const formValue = this.inspectionForm.getRawValue();
        const payload = {
            smp_id: formValue.smpId,
            inspection_number: formValue.inspectionNumber ?? null,
            controlling_authority: formValue.controllingAuthority,
            start_date: formValue.startDate,
            end_date: formValue.endDate,
            planned_duration: formValue.plannedDuration,
            status: formValue.status,
            notes: formValue.notes
        };

        if (this.isSaving) return;
        this.isSaving = true;
        this.serverErrors = null;

        try {
            let res: Response;
            if (this.isEditMode && formValue.inspectionId) {
                res = await fetch(`/api/inspections/${encodeURIComponent(formValue.inspectionId)}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
            } else {
                res = await fetch('/api/inspections', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
            }

            if (res.status === 201 || res.status === 200) {
                this.router.navigate(['/inspections'], { queryParams: { refresh: true } });

                return;
            }

            const json = await res.json().catch(() => null);
            if ((res.status === 422 || res.status === 400) && json && json.errors) {
                this.serverErrors = json.errors;
                Object.keys(json.errors).forEach((key) => {
                    const control = this.inspectionForm.get(this.mapServerFieldToFormControl(key));
                    if (control) {
                        control.setErrors({ server: json.errors[key] });
                    }
                });

                return;
            }

            if (json && json.message) this.serverErrors = { _global: json.message };
            else this.serverErrors = { _global: 'Не удалось сохранить запись' };
        } catch (e) {
            console.error('error: ', e);
            this.serverErrors = { _global: 'Ошибка сети или сервера' };
        } finally {
            this.isSaving = false;
        }
    }

    /**
     * Простая функция (по сути маппинг) для соответствия имён полей,
     * возвращаемых сервером, полям формы для установки ошибок на форму
     */
    private mapServerFieldToFormControl(field: string): string {
        switch (field) {
            case 'smp_id': return 'smpId';
            case 'controlling_authority': return 'controllingAuthority';
            case 'start_date': return 'startDate';
            case 'end_date': return 'endDate';
            case 'planned_duration': return 'plannedDuration';
            default: return field;
        }
    }

    onCancel(): void {
        this.router.navigate(['/inspections']);
    }

    // Режим добавки
    private patchFormForAdd(data: any): void {
        this.inspectionForm.patchValue({
            inspectionId: data.id || data.ID || null,
            smpText: data.smp_name || data.smpText || '',
            smpId: data.smp_id || data.smpId || null,
            controllingAuthority: data.controlling_authority || data.controllingAuthority || '',
            startDate: data.start_date || data.startDate || '',
            endDate: data.end_date || data.endDate || '',
            plannedDuration: data.planned_duration || data.plannedDuration || '',
            status: data.status || 'planned',
            notes: data.notes || ''
        });
        this.calculateDuration();
    }

    private async loadInspectionForEdit(id: number | string): Promise<void> {
        try {
            const res = await fetch(`/api/inspections/${encodeURIComponent(id)}`, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) {
                console.error('error: ' + res.status);
                return;
            }
            const json = await res.json();
            const data = json && json.data ? json.data : json;
            this.patchFormForAdd(data);
        } catch (e) {
            console.error('error:', e);
        }
    }
}
