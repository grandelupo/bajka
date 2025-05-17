# Reading Application Plan

## 1. Technology Stack
- Backend: Laravel 12.14.1
- Frontend: Vue 3 with Composition API
- Database: MySQL 8.x
- Authentication: Laravel Socialite (Google OAuth)
- Video Processing: FFmpeg for video optimization
- Audio Processing: FFmpeg for audio optimization
- Asset Storage: Local storage (can be migrated to S3 later)
- Payment Processing: Stripe API

## 2. Database Structure

### Users Table
```sql
users
- id (primary key)
- name
- email
- password
- google_id (nullable)
- avatar (nullable)
- coins (default: 0)
- created_at
- updated_at
```

### Books Table
```sql
books
- id (primary key)
- title
- description
- cover_image
- total_pages
- price_per_page (in coins)
- created_at
- updated_at
```

### Pages Table
```sql
pages
- id (primary key)
- book_id (foreign key)
- page_number
- content
- image_url
- video_url
- audio_url
- audio_duration
- created_at
- updated_at
```

### Transactions Table
```sql
transactions
- id (primary key)
- user_id (foreign key)
- book_id (foreign key)
- page_id (foreign key)
- coins_spent
- created_at
- updated_at
```

### Coin Purchases Table
```sql
coin_purchases
- id (primary key)
- user_id (foreign key)
- amount
- price
- status
- stripe_payment_id
- stripe_customer_id
- created_at
- updated_at
```

### Stripe Customers Table
```sql
stripe_customers
- id (primary key)
- user_id (foreign key)
- stripe_customer_id
- created_at
- updated_at
```

## 3. Views Structure

### Public Views
1. Homepage (`/`)
   - Hero section with app description
   - Features showcase
   - Sample book preview
   - Call-to-action buttons

2. Login Page (`/login`)
   - Email/password login form
   - Google OAuth button
   - Registration link

3. Register Page (`/register`)
   - Registration form
   - Google OAuth button
   - Login link

### Authenticated Views
1. Book Reader (`/reader/{book_id}`)
   - Desktop: Two-column layout
   - Mobile: Single column layout
   - Navigation controls
   - Progress indicator
   - Coin balance display
   - Audio player controls
   - Play/pause button
   - Audio progress bar
   - Volume control
   - Playback speed control

2. User Dashboard (`/dashboard`)
   - Reading history
   - Coin balance
   - Purchase history
   - Account settings

3. Coin Purchase Page (`/coins/purchase`)
   - Available coin packages
   - Payment integration
   - Transaction history

## 4. API Endpoints

### Authentication
- POST `/api/auth/login`
- POST `/api/auth/register`
- GET `/api/auth/google/redirect`
- GET `/api/auth/google/callback`
- POST `/api/auth/logout`

### Books
- GET `/api/books`
- GET `/api/books/{id}`
- GET `/api/books/{id}/pages/{page_number}`

### User
- GET `/api/user/profile`
- PUT `/api/user/profile`
- GET `/api/user/coins`
- GET `/api/user/transactions`

### Transactions
- POST `/api/transactions/purchase-coins`
- POST `/api/transactions/unlock-page`
- POST `/api/transactions/create-payment-intent`
- POST `/api/transactions/confirm-payment`
- GET `/api/transactions/payment-methods`
- POST `/api/transactions/save-payment-method`

### Stripe Webhooks
- POST `/api/webhooks/stripe`
  - Handle payment_intent.succeeded
  - Handle payment_intent.payment_failed
  - Handle customer.subscription.created
  - Handle customer.subscription.updated
  - Handle customer.subscription.deleted

## 5. Components Structure

### Layout Components
- `AppLayout.vue`
- `AuthLayout.vue`
- `ReaderLayout.vue`

### Common Components
- `Navigation.vue`
- `CoinBalance.vue`
- `PageControls.vue`
- `VideoPlayer.vue`
- `AudioPlayer.vue`
- `LoadingSpinner.vue`

### Feature Components
- `BookReader.vue`
- `PageView.vue`
- `CoinPurchase.vue`
- `UserProfile.vue`
- `StripePaymentForm.vue`
- `PaymentMethods.vue`
- `TransactionHistory.vue`
- `AudioControls.vue`

## 6. Implementation Phases

### Phase 1: Project Setup
1. Laravel project initialization
2. Vue integration
3. Database setup
4. Basic routing
5. Authentication system

### Phase 2: Core Features
1. Book reader implementation
2. Page navigation
3. Video/image loading system
4. Audio player implementation
   - Audio file loading
   - Playback controls
   - Progress tracking
   - Speed control
   - Volume management
5. Basic user dashboard

### Phase 3: Authentication & Authorization
1. Google OAuth integration
2. User registration/login
3. Protected routes
4. User profile management

### Phase 4: Monetization
1. Coin system implementation
2. Stripe integration
   - Payment intent creation
   - Payment confirmation
   - Webhook handling
   - Error handling
   - Payment method management
3. Purchase flow
4. Transaction tracking
5. Page access control

### Phase 5: Polish & Optimization
1. UI/UX improvements
2. Performance optimization
3. Video loading optimization
4. Mobile responsiveness
5. Testing

## 7. Security Considerations
- CSRF protection
- XSS prevention
- Input validation
- Rate limiting
- Secure file uploads
- Video access control
- Payment security
- PCI compliance
- Stripe webhook signature verification
- Secure storage of payment information

## 8. Performance Considerations
- Video preloading
- Image optimization
- Audio file optimization
- Lazy loading
- Caching strategies
- Database indexing
- API response optimization
- Payment processing optimization
- Webhook handling optimization
- Audio streaming optimization

## 9. Testing Strategy
- Unit tests for models
- Feature tests for controllers
- Component tests for Vue
- Integration tests
- E2E tests for critical flows

## 10. Deployment Considerations
- Environment configuration
- Database migrations
- Asset compilation
- SSL certificate
- Backup strategy
- Monitoring setup

## 11. Stripe Integration Details

### Payment Flow
1. User selects coin package
2. Frontend requests payment intent from backend
3. Backend creates Stripe payment intent
4. Frontend confirms payment using Stripe.js
5. Backend verifies payment and credits user's account
6. Webhook handles asynchronous payment events

### Coin Packages
- Basic: 100 coins for $5
- Standard: 500 coins for $20
- Premium: 1000 coins for $35
- Ultimate: 2500 coins for $75

### Error Handling
- Payment failures
- Network issues
- Invalid payment methods
- Insufficient funds
- Card declined scenarios

### Testing
- Stripe test mode integration
- Test card numbers
- Webhook testing
- Payment flow testing
- Error scenario testing

## 12. Audio Player Features

### Core Functionality
- Play/pause control
- Progress bar with seek functionality
- Volume control with mute option
- Playback speed control (0.5x - 2x)
- Time display (current/total)
- Auto-play next page option
- Background playback support
- Audio preloading for next page

### User Experience
- Smooth transitions between pages
- Persistent audio state during navigation
- Visual feedback for audio loading
- Keyboard shortcuts for controls
- Touch gestures for mobile
- Audio quality settings
- Offline playback support

### Technical Implementation
- Web Audio API integration
- Audio file format optimization
- Streaming implementation
- Buffer management
- Memory optimization
- Cross-browser compatibility
- Mobile device support
- Background playback handling

### Audio File Requirements
- Format: MP3/WebM
- Bitrate: 128kbps (standard), 256kbps (high quality)
- Sample rate: 44.1kHz
- Channels: Stereo
- Maximum file size per page: 5MB
- Total book size limit: 500MB 