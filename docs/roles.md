# ConnectID Roles

## Purpose

ConnectID roles define a user's position and permissions within the ConnectID ecosystem.

Roles are separate from reputation.

Reputation is earned through participation.

A role defines system permissions and responsibilities.

## Citizen

Every registered ConnectID user starts as a Citizen.

Citizen is the default role.

A Citizen can:

- maintain a ConnectID identity
- create connections
- exchange private messages with accepted connections
- join communities
- participate in the ConnectID ecosystem
- build reputation

## Creator

Creator is the highest ConnectID system role.

There is one permanent ConnectID Creator.

The Creator:

- founded ConnectID
- has the highest system authority
- cannot be assigned by another normal user
- cannot be selected during registration
- cannot be created through frontend code
- is controlled by the backend and database

The Creator role is separate from reputation.

A high reputation does not make a Citizen a Creator.

## Future roles

ConnectID may introduce additional roles such as:

- Moderator
- Community Leader
- Verified Citizen
- Community Manager

These roles should have clearly defined permissions.

## Role security

Roles must never be trusted from frontend data.

The server must determine a user's role from the authenticated account and database.

Users must never be able to submit:

- role
- permissions
- Creator status
- administrator status

as registration values.

## Role visibility

A user's current role may be displayed on their ConnectID profile.

The display should remain simple and subtle.

Example:

Creator

Citizen

Moderator

The role should not dominate the profile.

## Creator permanence

The Creator account is unique.

The system should prevent multiple Creator accounts from being created accidentally or maliciously.

Creator status should be assigned through a protected backend process.

## Reputation versus role

ConnectID uses two independent concepts:

Role = system responsibility

Reputation = earned trust

For example:

A Citizen may have high reputation.

A Creator may have low reputation.

Neither value automatically changes the other.

## Future governance

If ConnectID eventually introduces broader governance, the Creator role may remain the founding authority while additional governance mechanisms are introduced around it.

Such mechanisms should be designed separately from reputation.
