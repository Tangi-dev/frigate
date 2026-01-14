import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';

@Component({
    selector: 'app-menu',
    standalone: true,
    imports: [CommonModule, RouterModule],
    template: `
        <nav class="menu">
            <div class="menu-container">
                <div class="logo">
                    <h1><i class="bi bi-clipboard-check me-2"></i>Система проверок</h1>
                </div>
                <div class="nav-items">
                    <a [routerLink]="['/inspections']"
                       routerLinkActive="active"
                       [routerLinkActiveOptions]="{exact: true}"
                       class="nav-item">
                        <i class="bi bi-list-check me-1"></i>
                        Реестр плановых проверок
                    </a>
                </div>
            </div>
        </nav>
    `,
    styles: [`
        .menu {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .menu-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            height: 64px;
        }

        .logo h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            color: #ecf0f1;
            display: flex;
            align-items: center;
        }

        .nav-items {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .nav-item {
            color: #bdc3c7;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            font-size: 14px;
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateY(-1px);
        }

        .nav-item.active {
            background: rgba(52, 152, 219, 0.2);
            color: #3498db;
            border-left: 4px solid #3498db;
        }

        .user-info {
            background: rgba(255,255,255,0.1);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        @media (max-width: 768px) {
            .menu-container {
                flex-direction: column;
                height: auto;
                padding: 15px;
                gap: 15px;
            }

            .logo h1 {
                margin-bottom: 0;
                font-size: 18px;
            }

            .nav-items {
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
            }

            .nav-item {
                padding: 8px 16px;
                font-size: 13px;
            }

            .user-info {
                margin-top: 0;
            }
        }
    `]
})
export class MenuComponent {}