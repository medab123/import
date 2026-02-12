# Import Core System

This document outlines the core import system that provides shared functionality for all import services.

## 🏗️ Architecture Overview

The core system provides shared components used across all import services:

```
Core/
├── Contracts/           # Shared interfaces
├── DTOs/               # Shared Data Transfer Objects
├── Exceptions/         # Shared custom exceptions
├── Registry/           # Service registries
└── Traits/             # Shared functionality
```

## 📁 Service-Specific Directories

Each import service has its own directory with a consistent structure:

```
Downloader/          Reader/          Filter/
├── Abstracts/       ├── Abstracts/   ├── Abstracts/
├── Contracts/       ├── Contracts/   ├── Contracts/
├── Implementations/ ├── Implementations/ ├── Implementations/
├── Factories/       ├── Factories/   ├── Registry/
├── UI/              ├── UI/          ├── UI/
└── Providers/       └── Providers/   └── Providers/
```

## 🔧 Core Components

### Shared DTOs
- `FilterConfigurationData` - Filter configuration
- `FilterRuleData` - Individual filter rules
- `FilterResultData` - Filter results with statistics
- `OptionDefinition` - Option definitions for services

### Shared Exceptions
- `FilterException` - Filter-specific exceptions
- `ReaderException` - Reader-specific exceptions
- `DownloaderException` - Downloader-specific exceptions
- `InvalidOptionException` - Option validation exceptions

### Shared Contracts
- `ServiceInterface` - Base service interface
- `FactoryInterface` - Base factory interface

### Shared Traits
- `HasOptions` - Option management functionality
- `ServiceTrait` - Service type identification

## 🚀 Usage

The core system provides shared functionality that all import services can use. Each service (Downloader, Reader, Filter) has its own directory with a consistent structure.

For specific service documentation, see:
- [Downloader System](../Downloader/README.md)
- [Reader System](../Reader/README.md)  
- [Filter System](../Filter/README.md)

## 🧪 Testing

All core components are thoroughly tested with unit and integration tests. Each service directory contains its own test suite.

## 📈 Performance

The core system is designed for performance with:
- Efficient data structures
- Minimal memory usage
- Optimized algorithms
- Caching where appropriate

This core system provides a solid foundation for all import services while maintaining consistency and reusability.