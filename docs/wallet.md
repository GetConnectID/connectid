# ConnectID Wallet Architecture

## Purpose

A wallet is an optional part of a ConnectID identity.

ConnectID must remain useful without cryptocurrency.

## Wallet principle

A wallet does not equal identity.

A wallet does not equal reputation.

A wallet does not equal Citizen status.

## Privacy

ConnectID must not become a public wallet-to-profile reverse lookup service.

Users should not be able to enter an arbitrary wallet address and discover the private ConnectID identity behind it.

## Connection

A Citizen may optionally connect one wallet.

The initial system supports one wallet per ConnectID identity.

## Verification

Future wallet verification should prove control of the wallet without requiring the user to expose unnecessary information.

Possible future method:

1. Connect wallet
2. Sign a message
3. Verify signature
4. Store verification status
5. Never store private keys or seed phrases

## Reputation

Wallet ownership does not automatically award reputation.

Future verified blockchain activity may optionally contribute context to reputation, but only through explicit ConnectID rules.

## Non-crypto users

Users without a wallet must have access to:

- registration
- Citizen status
- profiles
- connections
- messaging
- communities
- reputation

A wallet must never be required for normal ConnectID use.

## Security

ConnectID must never request:

- seed phrases
- private keys
- exchange passwords
- recovery phrases

Only wallet signatures or public addresses may be used where necessary.
