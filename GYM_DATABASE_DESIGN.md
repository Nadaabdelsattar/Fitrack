# Gym Management System - SaaS-Ready Database Design

## Overview
This is a complete multi-tenant ready Gym Management System built with Laravel. Every table includes `gym_id` for future SaaS scalability.

---

## Database Tables & Structure

### 1. **GYMS** (Foundation)
- `id` - Primary key
- `name` - Gym name
- `email` - Gym email (unique)
- `phone` - Contact phone (nullable)
- `address` - Physical address (nullable)
- `timestamps` - created_at, updated_at

**Purpose:** Root table for multi-tenancy. Each gym is a separate tenant.

---

### 2. **USERS** (Admin, Staff, Trainers)
- `id` - Primary key
- `name` - User name
- `email` - Email (unique)
- `password` - Hashed password
- `role` - Enum: `admin`, `staff`, `trainer`
- `gym_id` - Foreign key → Gym (cascadeOnDelete)
- `email_verified_at` - Email verification timestamp
- `remember_token` - Remember me token
- `timestamps` - created_at, updated_at

**Purpose:** Only for management staff and trainers, NOT for gym members.
**Roles:**
- `admin` - Full system access for a gym
- `staff` - Support and administrative tasks
- `trainer` - Conducts classes and member interactions

---

### 3. **MEMBERS** (Gym Members/Customers)
- `id` - Primary key
- `gym_id` - Foreign key → Gym (cascadeOnDelete)
- `name` - Member name
- `email` - Member email (unique)
- `phone` - Contact phone (nullable)
- `gender` - Enum: `male`, `female`, `other` (nullable)
- `date_of_birth` - Date of birth (nullable)
- `status` - Enum: `active`, `inactive` (default: active)
- `timestamps` - created_at, updated_at

**Purpose:** Represents gym members/customers. Separate from users table.

---

### 4. **SUBSCRIPTIONS** (Membership Plans)
- `id` - Primary key
- `gym_id` - Foreign key → Gym (cascadeOnDelete)
- `member_id` - Foreign key → Member (cascadeOnDelete)
- `start_date` - Subscription start date
- `end_date` - Subscription end date
- `status` - Enum: `active`, `expired`, `paused` (default: active)
- `plan_name` - Plan name/type (nullable)
- `price` - Subscription price (decimal 10,2)
- `timestamps` - created_at, updated_at

**Purpose:** Track membership plans and duration.

---

### 5. **PAYMENTS** (Payment Records)
- `id` - Primary key
- `gym_id` - Foreign key → Gym (cascadeOnDelete)
- `member_id` - Foreign key → Member (cascadeOnDelete)
- `amount` - Payment amount (decimal 10,2)
- `payment_method` - Enum: `cash`, `card`, `online` (default: cash)
- `paid_at` - Payment timestamp
- `notes` - Additional notes (nullable)
- `timestamps` - created_at, updated_at

**Purpose:** Record all member payments.

---

### 6. **CLASSES** (Fitness Classes)
- `id` - Primary key
- `gym_id` - Foreign key → Gym (cascadeOnDelete)
- `trainer_id` - Foreign key → User (cascadeOnDelete)
- `name` - Class name
- `capacity` - Maximum class capacity (nullable)
- `schedule_time` - Class time (nullable)
- `timestamps` - created_at, updated_at

**Purpose:** Define fitness classes offered by the gym.

---

### 7. **ATTENDANCE** (Member Check-ins)
- `id` - Primary key
- `gym_id` - Foreign key → Gym (cascadeOnDelete)
- `member_id` - Foreign key → Member (cascadeOnDelete)
- `check_in_time` - Member check-in timestamp
- `check_out_time` - Member check-out timestamp (nullable)
- `timestamps` - created_at, updated_at

**Purpose:** Track member gym visits and session duration.

---

## Eloquent Model Relationships

### Gym Model
```php
Gym hasMany Users          // admin, staff, trainers
Gym hasMany Members        // gym members
Gym hasMany Classes        // GymClass
Gym hasMany Subscriptions
Gym hasMany Payments
Gym hasMany Attendance
```

### User Model
```php
User belongsTo Gym
User hasMany Classes       // As trainer_id
```

### Member Model
```php
Member belongsTo Gym
Member hasMany Subscriptions
Member hasMany Payments
Member hasMany Attendance
Member hasOne Subscription // Active subscription
```

### Subscription Model
```php
Subscription belongsTo Gym
Subscription belongsTo Member
Subscription hasHelper isActive()      // Check if currently active
Subscription hasHelper isExpired()     // Check if expired
```

### Payment Model
```php
Payment belongsTo Gym
Payment belongsTo Member
```

### GymClass Model
```php
GymClass belongsTo Gym
GymClass belongsTo User   // As trainer
```

### Attendance Model
```php
Attendance belongsTo Gym
Attendance belongsTo Member
Attendance hasHelper getDurationInMinutes() // Session duration
```

---

## File Structure

```
database/
├── migrations/
│   ├── 0001_01_01_000000_create_users_table.php (default Laravel)
│   ├── 2024_01_01_000001_create_gyms_table.php
│   ├── 2024_01_02_000001_modify_users_table_add_gym_and_role.php
│   ├── 2024_01_03_000001_create_members_table.php
│   ├── 2024_01_04_000001_create_subscriptions_table.php
│   ├── 2024_01_05_000001_create_payments_table.php
│   ├── 2024_01_06_000001_create_classes_table.php
│   └── 2024_01_07_000001_create_attendance_table.php

app/
└── Models/
    ├── User.php (updated)
    ├── Gym.php (new)
    ├── Member.php (new)
    ├── Subscription.php (new)
    ├── Payment.php (new)
    ├── GymClass.php (new)
    └── Attendance.php (new)
```

---

## SaaS-Ready Multi-Tenancy

Every table (except `gyms`) includes a `gym_id` field. This allows:

1. **Data Isolation:** Each gym's data is completely separated
2. **Query Scoping:** Always filter by `gym_id` in queries
3. **Soft Deletion Optional:** Can be added later to soft-delete entire gyms
4. **Scalability:** Easy to add more gyms without modifying schema

### Example Query with Scoping:
```php
// Get all members of a specific gym
$members = Member::where('gym_id', $gymId)->get();

// Get payment statistics for a gym
$payments = Payment::where('gym_id', $gymId)->sum('amount');
```

---

## Foreign Key Constraints

All foreign keys use **cascadeOnDelete** to maintain data integrity:
- Deleting a Gym → Deletes all related Users, Members, Classes, Subscriptions, Payments, Attendance
- Deleting a Member → Deletes all related Subscriptions, Payments, Attendance
- Deleting a User (trainer) → Deletes all related Classes

---

## Migration Order (Must Run in This Sequence)

1. `create_gyms_table` - Foundation
2. `modify_users_table_add_gym_and_role` - Add gym_id and role to users
3. `create_members_table` - Requires gym_id
4. `create_subscriptions_table` - Requires gym_id and member_id
5. `create_payments_table` - Requires gym_id and member_id
6. `create_classes_table` - Requires gym_id and trainer_id (user)
7. `create_attendance_table` - Requires gym_id and member_id

---

## Usage Examples

### Creating a Gym with Staff
```php
$gym = Gym::create([
    'name' => 'Fitness Pro Gym',
    'email' => 'info@fitnessgym.com',
    'phone' => '123-456-7890',
    'address' => '123 Main St, City'
]);

$admin = User::create([
    'name' => 'John Admin',
    'email' => 'john@gym.com',
    'password' => Hash::make('password'),
    'role' => 'admin',
    'gym_id' => $gym->id
]);
```

### Creating a Member and Subscription
```php
$member = Member::create([
    'gym_id' => $gym->id,
    'name' => 'Jane Doe',
    'email' => 'jane@example.com',
    'phone' => '987-654-3210',
    'gender' => 'female',
    'date_of_birth' => '1990-05-15',
    'status' => 'active'
]);

$subscription = Subscription::create([
    'gym_id' => $gym->id,
    'member_id' => $member->id,
    'start_date' => now(),
    'end_date' => now()->addMonths(1),
    'status' => 'active',
    'plan_name' => 'Monthly Plan',
    'price' => 49.99
]);
```

### Recording Attendance
```php
$attendance = Attendance::create([
    'gym_id' => $gym->id,
    'member_id' => $member->id,
    'check_in_time' => now(),
]);

// Later, check out the member
$attendance->update(['check_out_time' => now()]);
$duration = $attendance->getDurationInMinutes(); // Get session duration
```

---

## Production Checklist

- [x] Multi-tenancy support with gym_id on all tables
- [x] Foreign key constraints with cascadeOnDelete
- [x] Proper Eloquent relationships
- [x] Type casting for dates and decimals
- [x] Mass assignable fields defined
- [x] Helper methods for common operations
- [x] Indexes on foreign keys for query performance
- [x] SaaS-ready architecture

Ready for deployment!
