import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { PlannedInspectionsComponent } from './planned-inspection/planned-inspections.component';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [
    CommonModule,
    PlannedInspectionsComponent
  ],
  template: `
    <div class="app-container">
      <header class="app-header">
        <h1>Система управления плановыми проверками</h1>
      </header>

      <main class="app-main">
        <app-planned-inspections></app-planned-inspections>
      </main>

      <footer class="app-footer">
        <p>© бла бла бла.</p>
      </footer>
    </div>
  `,
  styles: [`
    .app-container {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      background-color: #f5f5f5;
    }

    .app-header {
      background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
      color: white;
      padding: 16px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);

      h1 {
        margin: 0;
        font-size: 24px;
        font-weight: 500;
      }

      .user-info {
        font-size: 14px;
        opacity: 0.9;
      }
    }

    .app-main {
      flex: 1;
      padding: 20px;
      max-width: 1400px;
      margin: 0 auto;
      width: 100%;
    }

    .app-footer {
      background-color: #333;
      color: white;
      padding: 16px;
      text-align: center;
      font-size: 14px;

      p {
        margin: 0;
      }
    }

    @media (max-width: 768px) {
      .app-header {
        flex-direction: column;
        gap: 8px;
        text-align: center;
      }

      .app-main {
        padding: 12px;
      }
    }
  `]
})
export class App {
  title = 'Плановые проверки';
}