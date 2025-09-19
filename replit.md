# Overview

This is a comprehensive website analysis application built with Laravel for the backend and React for the frontend using Inertia.js. The application provides detailed website analysis capabilities with AI-powered insights, including SEO analysis, performance metrics, and competitor analysis. The system is designed with full Arabic language support and RTL layout.

**Recent Updates (September 19, 2025)**: Successfully completed development of advanced Business Analysis Platform (AnalyzerDropidea) with specialized business search and analysis capabilities. Implemented tabbed interface for choosing between website URL analysis and business search with Google Places integration.

# User Preferences

Preferred communication style: Simple, everyday language.
Response language: Always respond in Arabic language.

# System Architecture

## Backend Framework
The application uses Laravel 10.x as the primary backend framework, providing a robust MVC architecture with built-in authentication, routing, and database management capabilities. The backend is responsible for handling website analysis logic, managing AI integrations, and serving API endpoints.

## Frontend Technology Stack
The frontend is built using React 18.x with Inertia.js serving as the bridge between Laravel and React. This approach eliminates the need for a separate API layer while maintaining the benefits of a single-page application experience. Tailwind CSS is used for styling with comprehensive RTL support for Arabic language interfaces.

## Database Layer
The application uses SQLite as the default database, providing a lightweight and file-based storage solution that's ideal for development and small to medium-scale deployments. The database contains:

### Basic Tables:
- **Users table**: User accounts with authentication data  
- **AI API Settings**: OpenAI API configuration per user
- **Website Analyses**: Basic analysis records with JSON data storage
- **System tables**: Laravel migrations, failed jobs, and access tokens

### Advanced Tables (AnalyzerDropidea Phase 1):
- **website_analyses_advanced**: Comprehensive analysis records with detailed scoring system
- **gmb_entities**: Google My Business data integration
- **competitors**: Competitor analysis and comparison data  
- **audit_runs**: Detailed audit tracking for performance, security, and SEO checks

**Database Status**: Fully populated with imported data including 1 user account (ababneh@gmail.com), 1 AI API configuration, and advanced analysis capabilities through multiple specialized tables.

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

# AnalyzerDropidea - Advanced Analysis Platform

## Phase 1 Implementation (Completed September 18, 2025)

## Phase 2 - Business Analysis Implementation (Completed September 19, 2025)

### Advanced Services Architecture:
- **GooglePlacesService**: Google Places API integration for business data
- **PageSpeedService**: Google PageSpeed Insights with Core Web Vitals
- **WappalyzerService**: Technology detection and stack analysis
- **SecurityAnalysisService**: SSL/TLS analysis and security headers evaluation
- **AdvancedWebsiteAnalyzerService**: Comprehensive analysis orchestration
- **AIAnalysisService**: Enhanced AI integration with fallback mechanisms

### Frontend Components:
- **AnalyzerDropidea.jsx**: Advanced React interface with tabbed navigation
- **Interactive scoring system**: Circular progress indicators for performance metrics
- **Real-time analysis**: Dynamic loading states and error handling
- **Google Places integration**: Business search and selection functionality

### Advanced Features:
- **Multi-layered analysis**: Performance, security, SEO, technologies, and AI insights
- **Composite scoring system**: Weighted scoring algorithm for overall assessment
- **Audit tracking**: Detailed logging of analysis runs and performance
- **Business intelligence**: Google My Business integration and competitor analysis

### API Routes (Advanced):
- `/dropidea` - Main advanced analysis interface
- `/dropidea/analyze` - Comprehensive website analysis endpoint
- `/dropidea/search-business` - Google Places business search
- `/dropidea/analysis/{id}` - View detailed analysis results

### Advanced Business Analysis Features (Phase 2):
- **Business Search**: Integration with Google Places API for finding specialized businesses
- **Tabbed Interface**: Clean user interface with tabs for choosing between URL analysis and business search
- **Business Categories**: 12 specialized business categories including restaurants, beauty salons, law firms, hospitals, schools, gyms, shopping malls, car repair shops, real estate agencies, accounting firms, pharmacies, and gas stations
- **Location-Based Search**: Support for major Saudi cities (Riyadh, Jeddah, Dammam, Mecca, Medina, Tabuk, Abha, Taif)
- **Interactive Suggestions**: Real-time business search suggestions with ratings and addresses
- **Comprehensive Analysis**: Specialized business analysis including Google My Business data integration
- **Arabic Interface**: Full RTL support with Arabic labels and icons for all business categories

### User Authentication:
- **Test Account**: ababneh@gmail.com / Aa123456789@#
- **Access Level**: Full access to basic, advanced, and business analysis tools

### Current Routes (Business Analysis):
- `/analyzer` - Main business analyzer interface with tabbed navigation
- `/analyzer/analyze` - Website URL analysis endpoint
- `/analyzer/search-business` - Google Places business search endpoint (GET)
- `/analyzer/analyze-business` - Business analysis endpoint (POST)
- `/analyzer/history` - Analysis history view
- `/analyzer/report/{id}` - View detailed analysis results
- `/analyzer/report/{id}/pdf` - Download PDF analysis reports