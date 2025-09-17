# Overview

This is a comprehensive website analysis application built with Laravel for the backend and React for the frontend using Inertia.js. The application provides detailed website analysis capabilities with AI-powered insights, including SEO analysis, performance metrics, and competitor analysis. The system is designed with full Arabic language support and RTL layout.

# User Preferences

Preferred communication style: Simple, everyday language.

# System Architecture

## Backend Framework
The application uses Laravel 10.x as the primary backend framework, providing a robust MVC architecture with built-in authentication, routing, and database management capabilities. The backend is responsible for handling website analysis logic, managing AI integrations, and serving API endpoints.

## Frontend Technology Stack
The frontend is built using React 18.x with Inertia.js serving as the bridge between Laravel and React. This approach eliminates the need for a separate API layer while maintaining the benefits of a single-page application experience. Tailwind CSS is used for styling with comprehensive RTL support for Arabic language interfaces.

## Database Layer
The application uses SQLite as the default database, providing a lightweight and file-based storage solution that's ideal for development and small to medium-scale deployments. The database schema includes user management and website analysis data storage.

## Authentication System
Laravel Breeze is integrated to provide a complete authentication system including user registration, login, password reset, and email verification functionality. The authentication views and components are customized for Arabic language support.

## AI Integration Architecture
The system supports multiple AI service providers including OpenAI, Anthropic, and Manus AI. This multi-provider approach ensures flexibility and redundancy in AI-powered analysis features. API keys are managed through a dedicated settings system.

## PDF Generation
Laravel DomPDF is integrated for generating detailed analysis reports that can be exported as PDF documents. This feature supports Arabic text rendering and RTL layouts.

## Asset Compilation
Vite is used as the build tool for frontend assets, providing fast development builds and optimized production bundles. The configuration supports React JSX compilation and CSS processing with PostCSS and Tailwind.

## Internationalization
The application is built with comprehensive Arabic language support, including RTL text direction, Arabic font families (Cairo, Tajawal, Almarai), and culturally appropriate UI patterns.

# External Dependencies

## Core Framework Dependencies
- **Laravel Framework**: ^10.10 - Primary backend framework
- **Inertia.js Laravel**: ^0.6.3 - Server-side adapter for React integration
- **Laravel Sanctum**: ^3.2 - API authentication system
- **Laravel Breeze**: ^1.29 - Authentication scaffolding

## Frontend Dependencies
- **React**: ^18.3.1 - Frontend JavaScript library
- **React DOM**: ^18.3.1 - React rendering library
- **Inertia.js React**: ^1.3.0 - Client-side Inertia adapter
- **Headless UI React**: ^1.4.2 - Unstyled UI components
- **Tailwind CSS**: ^3.2.1 - Utility-first CSS framework
- **Tailwind Forms**: ^0.5.3 - Form styling plugin

## HTTP and API Integration
- **Guzzle HTTP**: ^7.2 - HTTP client for external API requests
- **Axios**: ^1.6.4 - Frontend HTTP client for AJAX requests

## PDF Generation
- **Laravel DomPDF**: ^3.1 - PDF generation from HTML

## Development Tools
- **Vite**: ^5.4.20 - Build tool and development server
- **Vite React Plugin**: ^4.7.0 - React support for Vite
- **Laravel Vite Plugin**: ^1.3.0 - Laravel integration for Vite
- **PostCSS**: ^8.4.31 - CSS processing tool
- **Autoprefixer**: ^10.4.12 - CSS vendor prefixing

## Route Management
- **Ziggy**: ^2.0 - Laravel routes in JavaScript