# ConnectID Database

## users

The users table represents the core ConnectID Identity.

Fields:

- id
- username
- display_name
- password_hash
- avatar
- citizen
- role
- reputation
- wallet_address
- created_at
- updated_at
- status

---

## connections

Stores mutual connection relationships.

Fields:

- id
- requester_id
- receiver_id
- status
- created_at
- updated_at

Possible statuses:

- pending
- accepted
- declined
- blocked

---

## messages

Stores private messages.

Fields:

- id
- sender_id
- receiver_id
- message
- created_at
- read_at

---

## communities

Stores ConnectID communities.

Fields:

- id
- name
- description
- creator_id
- created_at
- updated_at
- status

---

## community_members

Connects Citizens with communities.

Fields:

- id
- community_id
- user_id
- role
- joined_at

---

# Future Tables

Possible future tables:

- reputation_events
- wallet_verifications
- reports
- notifications
- sessions
