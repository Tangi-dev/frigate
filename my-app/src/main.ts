// main.ts
import { bootstrapApplication } from '@angular/platform-browser';
import { ApplicationConfig, importProvidersFrom } from '@angular/core';
import { provideRouter, Routes } from '@angular/router';
import { provideAnimations } from '@angular/platform-browser/animations';
import { provideHttpClient } from '@angular/common/http';

// Material импорты
import { MatNativeDateModule } from '@angular/material/core';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatDialogModule } from '@angular/material/dialog';

import { AppComponent } from './app/app.component';
import { PlannedInspectionsComponent } from './app/planned-inspection/planned-inspections.component';
import { AddInspectionComponent } from './app/add-inspection-dialog/add-inspection-dialog.component';

const routes: Routes = [
    { path: '', redirectTo: '/inspections', pathMatch: 'full' },
    { path: 'inspections', component: PlannedInspectionsComponent },
    { path: 'inspections/add', component: AddInspectionComponent },
    { path: 'inspections/edit/:id', component: AddInspectionComponent },
    { path: '**', redirectTo: '/inspections' }
];

export const appConfig: ApplicationConfig = {
    providers: [
        provideRouter(routes),
        provideAnimations(),
        provideHttpClient(),
        importProvidersFrom(
            MatNativeDateModule,
            MatDatepickerModule,
            MatDialogModule
        )
    ]
};

bootstrapApplication(AppComponent, appConfig)
    .catch(err => console.error(err));