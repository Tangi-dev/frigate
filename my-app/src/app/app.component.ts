import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, RouterOutlet } from '@angular/router';
import { MenuComponent } from './menu/menu.component';

@Component({
    selector: 'app-root',
    standalone: true,
    imports: [
        CommonModule,
        RouterModule,
        RouterOutlet,
        MenuComponent
    ],
    template: `
        <div class="app-container">
            <app-menu></app-menu>
            <main class="app-main">
                <router-outlet></router-outlet>
            </main>
        </div>
    `,
    styles: [`
    .app-container {
      min-height: 100vh;
      background-color: #f5f7fa;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
    }
    
    .app-main {
      padding: 20px;
      min-height: calc(100vh - 60px);
    }
    
    @media (max-width: 768px) {
      .app-main {
        padding: 15px;
      }
    }
  `]
})
export class AppComponent {}