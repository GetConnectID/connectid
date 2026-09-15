# ConnectID Deployment

## Hosting

The initial production environment is planned for PHP and MySQL hosting.

## Application

The application contains:

- public website
- ConnectID application
- PHP backend
- MySQL database

## Production configuration

Database credentials must never be committed to GitHub.

Production configuration should exist on the hosting server.

## Database

The production database must contain the required ConnectID tables before the application is activated.

## HTTPS

ConnectID must use HTTPS in production.

## File structure

The production environment should preserve the application structure:

website/

app/

app/backend/

docs/

## Security

Before production:

- enable HTTPS
- configure secure sessions
- configure database credentials
- verify file permissions
- remove development credentials
- verify error handling
- create backups

## Deployment principle

GitHub contains the application source.

Production secrets remain on the hosting environment.

The public repository must never contain production credentials.
