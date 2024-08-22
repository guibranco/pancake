# Database Wrapper Documentation

## Overview

The Database Wrapper provides an abstraction layer over the database operations, allowing for simplified and consistent interaction with the database.

## Configuration

The wrapper can be configured using environment variables or a configuration dictionary passed during initialization.

## Usage

1. **Initialization**: Create an instance of the `DatabaseWrapper` with the necessary configuration.
2. **Connection**: Use the `connect` method to establish a connection to the database.
3. **Operations**: Execute queries and fetch results using `execute_query` and `fetch_results` methods.
4. **Disconnection**: Use the `disconnect` method to close the connection.
