# Loan Management System - Complete Overview

## 🎯 System Architecture

```
┌──────────────────────────────────────────────────────────────────┐
│                    LOAN MANAGEMENT SYSTEM                         │
│                                                                   │
│  ┌─────────────────────┐         ┌─────────────────────┐        │
│  │   CUSTOMER SIDE     │         │     ADMIN SIDE      │        │
│  │                     │         │                     │        │
│  │  • Register         │         │  • Login            │        │
│  │  • Upload Docs      │         │  • Review Apps      │        │
│  │  • Apply for Loan   │         │  • Verify Docs      │        │
│  │  • Track Status     │         │  • Assign Rates     │        │
│  │  • View Loans       │         │  • Approve/Deny     │        │
│  │  • Make Payments    │         │  • Manage Loans     │        │
│  └─────────────────────┘         └─────────────────────┘        │
│            │                               │                      │
│            └───────────┬───────────────────┘                      │
│                        ▼                                          │
│          ┌──────────────────────────┐                           │
│          │   CORE CALCULATION       │                           │
│          │      ENGINE              │                           │
│          │                          │                           │
│          │  • Auto 18% (≤K5,000)   │                           │
│          │  • Admin Rate (>K5,000) │                           │
│          │  • Simple Interest      │                           │
│          │  • Compound Interest    │                           │
│          │  • Amortization         │                           │
│          └──────────────────────────┘                           │
│                        │                                          │
│                        ▼                                          │
│          ┌──────────────────────────┐                           │
│          │      DATABASE            │                           │
│          │                          │                           │
│          │  • loan_list             │                           │
│          │  • borrowers             │                           │
│          │  • borrower_documents    │                           │
│          │  • payments              │                           │
│          │  • notifications         │                           │
│          └──────────────────────────┘                           │
└──────────────────────────────────────────────────────────────────┘
```

---

## 🔄 Complete Loan Lifecycle

```
┌───────────────────────────────────────────────────────────────────┐
│                     LOAN APPLICATION FLOW                         │
└───────────────────────────────────────────────────────────────────┘

1. CUSTOMER REGISTRATION
   ┌─────────────────────┐
   │ Customer Registers  │
   │ - Name, Email       │
   │ - Contact, Address  │
   └──────────┬──────────┘
              │
              ▼
   ┌─────────────────────┐
   │ Upload Documents    │
   │ - ID Document       │
   │ - Employment Proof  │
   │ - Pay Slip          │
   └──────────┬──────────┘
              │
              ▼
   Status: Documents Pending Verification

───────────────────────────────────────────────────────────────────

2. LOAN APPLICATION
   ┌─────────────────────┐
   │ Fill Application    │
   │ - Loan Type         │
   │ - Amount            │
   │ - Purpose           │
   │ - Duration          │
   └──────────┬──────────┘
              │
              ▼
   ┌─────────────────────┐
   │ System Calculates   │
   │                     │
   │ IF Amount ≤ K5,000: │
   │  → 18% Auto-Assign  │
   │                     │
   │ IF Amount > K5,000: │
   │  → 0% (Pending)     │
   └──────────┬──────────┘
              │
              ▼
   ┌─────────────────────┐
   │ Submit Application  │
   │ - Reference# Gen    │
   │ - Status: Pending   │
   │ - Notification Sent │
   └──────────┬──────────┘
              │
              ▼
   Status: Application Pending Review

───────────────────────────────────────────────────────────────────

3. ADMIN REVIEW
   ┌─────────────────────┐
   │ Admin Opens Review  │
   │ Interface           │
   └──────────┬──────────┘
              │
              ▼
   ┌─────────────────────┐
   │ Review Loan Details │
   │ - Borrower Info     │
   │ - Loan Amount       │
   │ - Purpose           │
   │ - Duration          │
   └──────────┬──────────┘
              │
              ▼
   ┌─────────────────────┐
   │ Review Documents    │
   │                     │
   │ For Each Document:  │
   │  → View Inline      │
   │  → Verify ✓         │
   │  → Reject ✗         │
   └──────────┬──────────┘
              │
              ▼
   ┌─────────────────────────────┐
   │ Check Interest Rate         │
   │                             │
   │ IF ≤ K5,000:                │
   │  → Already 18%              │
   │  → No action needed         │
   │                             │
   │ IF > K5,000:                │
   │  → Select rate (25-40%)     │
   │  → See calculation preview  │
   │  → Approve button enabled   │
   └──────────┬──────────────────┘
              │
              ▼
   ┌─────────────────────┐
   │ Make Decision       │
   │                     │
   │ Option A: APPROVE   │
   │  → Recalculate loan │
   │  → Update status    │
   │  → Notify customer  │
   │                     │
   │ Option B: DENY      │
   │  → Enter reason     │
   │  → Update status    │
   │  → Notify customer  │
   └──────────┬──────────┘
              │
              ├─────────┬─────────┐
              ▼         ▼         ▼
      ┌─────────┐ ┌─────────┐ ┌─────────┐
      │Approved │ │Released │ │ Denied  │
      │Status=1 │ │Status=2 │ │Status=4 │
      └─────────┘ └─────────┘ └─────────┘

───────────────────────────────────────────────────────────────────

4. CUSTOMER NOTIFICATION
   ┌─────────────────────┐
   │ Customer Receives   │
   │ Notification        │
   │                     │
   │ IF APPROVED:        │
   │  → Rate: X%         │
   │  → Total: K X,XXX   │
   │  → Monthly: K XXX   │
   │  → Duration: X mo   │
   │                     │
   │ IF DENIED:          │
   │  → Reason provided  │
   │  → Can reapply      │
   └─────────────────────┘
```

---

## 🧮 Calculation Flow

```
┌───────────────────────────────────────────────────────────────────┐
│                  INTEREST RATE CALCULATION FLOW                   │
└───────────────────────────────────────────────────────────────────┘

STEP 1: Determine Interest Rate
────────────────────────────────
┌─────────────────────┐
│ Check Loan Amount   │
└──────────┬──────────┘
           │
           ├───────────────────────────────────┐
           │                                   │
           ▼                                   ▼
┌──────────────────────┐          ┌──────────────────────┐
│  Amount ≤ K5,000     │          │  Amount > K5,000     │
│                      │          │                      │
│  AUTOMATIC           │          │  MANUAL              │
│  Rate = 18%          │          │  Rate = 0%           │
│  Type = Simple       │          │  Type = Simple       │
│                      │          │                      │
│  ✓ Ready for Review  │          │  ⏳ Needs Assignment │
└──────────┬───────────┘          └──────────┬───────────┘
           │                                   │
           │                                   ▼
           │                      ┌──────────────────────┐
           │                      │ Admin Selects Rate   │
           │                      │ - 25%                │
           │                      │ - 28%                │
           │                      │ - 30%                │
           │                      │ - 35%                │
           │                      │ - 40%                │
           │                      └──────────┬───────────┘
           │                                   │
           └───────────────────┬───────────────┘
                               │
                               ▼

STEP 2: Calculate Loan Values
──────────────────────────────
┌─────────────────────────────────────────┐
│ Input:                                  │
│  • Principal (P)                        │
│  • Interest Rate (R) - Annual %         │
│  • Duration (N) - Months                │
│  • Calculation Type (Simple/Compound)   │
└──────────────────┬──────────────────────┘
                   │
                   ├──────────────────────────────┐
                   │                              │
                   ▼                              ▼
┌─────────────────────────────┐    ┌─────────────────────────────┐
│   SIMPLE INTEREST           │    │   COMPOUND INTEREST         │
│                             │    │                             │
│ 1. Monthly Rate:            │    │ 1. Monthly Rate:            │
│    r = R / 12 / 100         │    │    r = R / 12 / 100         │
│                             │    │                             │
│ 2. Total Interest:          │    │ 2. Total Payable:           │
│    I = P × r × N            │    │    A = P × (1 + r)^N        │
│                             │    │                             │
│ 3. Total Payable:           │    │ 3. Total Interest:          │
│    A = P + I                │    │    I = A - P                │
│                             │    │                             │
│ 4. Monthly Payment:         │    │ 4. Monthly Payment:         │
│    M = A / N                │    │    M = P×r×(1+r)^N          │
│                             │    │        ───────────           │
│                             │    │        (1+r)^N - 1          │
└──────────────┬──────────────┘    └──────────────┬──────────────┘
               │                                   │
               └──────────────┬────────────────────┘
                              │
                              ▼
STEP 3: Store Results
─────────────────────
┌─────────────────────────────────────────┐
│ Save to Database:                       │
│  • interest_rate                        │
│  • calculation_type                     │
│  • total_interest                       │
│  • total_payable                        │
│  • monthly_installment                  │
│  • outstanding_balance                  │
└─────────────────────────────────────────┘
```

---

## 📊 Example Calculations

### Example 1: Small Loan (Auto 18%)
```
INPUT:
• Amount: K3,000
• Rate: 18% (Auto-assigned)
• Duration: 12 months
• Type: Simple

CALCULATION:
Monthly Rate = 18 / 12 / 100 = 0.015 (1.5%)
Total Interest = 3,000 × 0.015 × 12 = K540
Total Payable = 3,000 + 540 = K3,540
Monthly Payment = 3,540 / 12 = K295

RESULT:
✓ Customer pays K295/month for 12 months
✓ Total payment: K3,540
✓ Interest paid: K540
```

### Example 2: Large Loan (Admin 30%)
```
INPUT:
• Amount: K10,000
• Rate: 30% (Admin-assigned)
• Duration: 24 months
• Type: Simple

CALCULATION:
Monthly Rate = 30 / 12 / 100 = 0.025 (2.5%)
Total Interest = 10,000 × 0.025 × 24 = K6,000
Total Payable = 10,000 + 6,000 = K16,000
Monthly Payment = 16,000 / 24 = K666.67

RESULT:
✓ Customer pays K666.67/month for 24 months
✓ Total payment: K16,000
✓ Interest paid: K6,000
```

### Example 3: Compound Interest Comparison
```
SAME LOAN, DIFFERENT METHOD:
• Amount: K5,000
• Rate: 28%
• Duration: 12 months

SIMPLE INTEREST:
Monthly Rate = 28 / 12 / 100 = 0.0233
Total Interest = 5,000 × 0.0233 × 12 = K1,400
Total Payable = K6,400
Monthly Payment = K533.33

COMPOUND INTEREST:
Monthly Rate = 0.0233
Total Payable = 5,000 × (1.0233)^12 = K6,598
Total Interest = K1,598
Monthly Payment = K549.83

DIFFERENCE:
Compound costs K198 more over the loan period
(14% more expensive)
```

---

## 🎨 UI Component Map

```
┌───────────────────────────────────────────────────────────────────┐
│                    ADMIN INTERFACE STRUCTURE                      │
└───────────────────────────────────────────────────────────────────┘

NAVBAR (navbar.php)
├── Home
├── 🆕 Loan Applications Review ← NEW MENU ITEM
├── Loans
├── Payments
├── Borrowers
├── Loan Plans
├── Loan Types
└── Users

LOAN APPLICATIONS REVIEW PAGE (loan_applications_review.php)
├── Tabs
│   ├── Pending Review (Badge: count)
│   ├── Approved (Badge: count)
│   └── Denied (Badge: count)
├── Table
│   ├── Reference Number
│   ├── Borrower Name & Email
│   ├── Loan Amount
│   ├── Interest Rate (18% or "Not Set")
│   ├── Duration
│   ├── Date Applied
│   ├── Document Status (✓Verified ⚠Pending ○Total)
│   └── [Review Button]
└── Modal (Review)
    ├── Load: loan_review_details.php
    └── Size: Extra Large (modal-xl)

LOAN REVIEW DETAILS MODAL (loan_review_details.php)
├── Left Column
│   ├── Borrower Information
│   │   ├── Name
│   │   ├── Tax ID
│   │   ├── Email
│   │   ├── Contact
│   │   └── Address
│   ├── Loan Details
│   │   ├── Reference Number
│   │   ├── Loan Type
│   │   ├── Amount
│   │   ├── Duration
│   │   ├── Purpose
│   │   └── Application Date
│   └── Interest Rate Section
│       ├── IF ≤ K5,000: "Auto-Assigned 18%" badge
│       ├── IF > K5,000:
│       │   ├── Rate dropdown (25-40%)
│       │   └── Calculation Preview (updates on change)
│       └── Calculation Display
│           ├── Principal
│           ├── Interest Rate
│           ├── Total Interest
│           ├── Total Payable
│           └── Monthly Payment
└── Right Column
    ├── Documents Section
    │   └── For Each Document:
    │       ├── Document Type
    │       ├── Upload Date
    │       ├── Status Badge (Pending/Verified/Rejected)
    │       ├── [View Button]
    │       ├── [Verify Button] (if pending)
    │       └── [Reject Button] (if pending)
    └── Document Viewer
        ├── Image Preview (JPG, PNG)
        ├── PDF Embed
        └── Download Link
└── Action Buttons (Bottom)
    ├── [Cancel] - Close modal
    ├── [Deny] - Reject application
    └── [Approve] - Accept application
        └── Disabled until rate assigned (if needed)
```

---

## 🔐 Security Integration

```
┌───────────────────────────────────────────────────────────────────┐
│                       SECURITY LAYERS                             │
└───────────────────────────────────────────────────────────────────┘

LAYER 1: SESSION MANAGEMENT
───────────────────────────
• Session start with Security::secureSession()
• Login verification on every page
• Session timeout after inactivity
• Session fixation prevention

LAYER 2: INPUT VALIDATION
──────────────────────────
• Security::sanitizeInt() - Integer inputs
• Security::sanitizeFloat() - Decimal inputs
• Security::sanitizeString() - Text inputs
• Prepared statements for SQL

LAYER 3: PAGE ACCESS CONTROL
─────────────────────────────
Whitelist in admin.php:
✓ home
✓ loan_applications_review
✓ loans
✓ payments
✓ borrowers
✓ manage_loan
✗ Any other page = blocked

LAYER 4: CSRF PROTECTION
─────────────────────────
• Token generated: Security::generateCSRFToken()
• Auto-added to all forms
• Validated on form submission

LAYER 5: FILE UPLOAD SECURITY
──────────────────────────────
• Type validation (JPG, PNG, PDF only)
• Size limit (5MB max)
• MIME type verification
• Unique filename generation
• Storage outside web root recommended

LAYER 6: SQL INJECTION PREVENTION
──────────────────────────────────
All queries use prepared statements:
✓ $stmt->prepare("SELECT * WHERE id = ?")
✓ $stmt->bind_param("i", $id)
✗ Never: "SELECT * WHERE id = $id"

LAYER 7: XSS PREVENTION
───────────────────────
• htmlspecialchars() on output
• Input sanitization on input
• Content Security Policy headers
```

---

## 📈 Performance Optimization

```
┌───────────────────────────────────────────────────────────────────┐
│                    OPTIMIZATION STRATEGIES                        │
└───────────────────────────────────────────────────────────────────┘

DATABASE OPTIMIZATION
─────────────────────
Recommended Indexes:
• loan_list(status) - For filtering by status
• loan_list(borrower_id) - For customer lookups
• loan_list(date_created) - For sorting
• borrower_documents(borrower_id, status) - For doc queries
• payments(loan_id) - For payment history

QUERY OPTIMIZATION
──────────────────
• Use JOINs instead of multiple queries
• Fetch only needed columns
• Use LIMIT for pagination
• Cache frequently accessed data

AJAX OPTIMIZATION
─────────────────
• Load modals on demand (not page load)
• Use jQuery .one() for single-use handlers
• Implement loading indicators
• Handle errors gracefully

FILE HANDLING
─────────────
• Store files outside web root
• Use CDN for static assets
• Implement lazy loading for images
• Compress uploaded images

CACHING STRATEGY
────────────────
• Cache loan plans (rarely change)
• Cache loan types (rarely change)
• Session caching for user data
• Browser caching for static assets
```

---

## 🎯 Key Metrics to Monitor

```
┌───────────────────────────────────────────────────────────────────┐
│                      MONITORING DASHBOARD                         │
└───────────────────────────────────────────────────────────────────┘

LOAN METRICS
────────────
• Pending Applications: Count loans where status = 0
• Average Review Time: Time from application to approval/denial
• Approval Rate: (Approved / Total Applications) × 100
• Denial Rate: (Denied / Total Applications) × 100
• Average Loan Amount: AVG(amount) WHERE status IN (1,2,3)

DOCUMENT METRICS
────────────────
• Documents Pending: COUNT WHERE status = 0
• Documents Verified: COUNT WHERE status = 1
• Documents Rejected: COUNT WHERE status = 2
• Average Verification Time: Time from upload to verification

INTEREST RATE METRICS
─────────────────────
• Auto-Assigned (18%): COUNT WHERE amount <= 5000
• Admin-Assigned: COUNT WHERE amount > 5000
• Most Common Rate: MODE(interest_rate) WHERE amount > 5000
• Average Rate: AVG(interest_rate) WHERE status IN (1,2,3)

SYSTEM HEALTH
─────────────
• AJAX Error Rate: Failed requests / Total requests
• Page Load Time: Average response time
• Database Connection Time: Average query time
• Session Timeout Rate: Expired sessions / Total sessions
```

---

## ✅ Final Integration Checklist

```
PRE-DEPLOYMENT
──────────────
☐ All files uploaded to server
☐ Database migrations run
☐ File permissions set (644 for PHP, 755 for directories)
☐ Error reporting configured
☐ Database credentials secure

NAVIGATION
──────────
☐ Menu item appears in admin navbar
☐ Clicks correctly load review page
☐ Active state highlights properly

FUNCTIONALITY
─────────────
☐ Small loans auto-assign 18%
☐ Large loans require admin rate
☐ Documents display in viewer
☐ Verify/Reject buttons work
☐ Interest rate dropdown works
☐ Calculation preview updates
☐ Approve button works
☐ Deny button works
☐ Notifications created

CALCULATIONS
────────────
☐ Simple interest formula correct
☐ Compound interest formula correct
☐ Monthly rate conversion accurate
☐ Loan calculator matches backend
☐ Payment schedule accurate

SECURITY
────────
☐ CSRF tokens present
☐ SQL injection prevented
☐ XSS protection active
☐ File upload validated
☐ Session management secure
☐ Page whitelist enforced

POST-DEPLOYMENT
───────────────
☐ Monitor error logs
☐ Test with real data
☐ Verify email notifications (if configured)
☐ Check performance metrics
☐ User acceptance testing
```

---

## 🎓 Training Guide

### For Administrators

**Daily Tasks:**
1. Check "Loan Applications Review" menu
2. Review pending applications
3. Verify documents
4. Assign interest rates (for large loans)
5. Approve or deny applications

**Best Practices:**
- Review documents carefully before verification
- Check borrower information completeness
- Verify employment and income documents
- Consider loan amount vs. income ratio
- Document denial reasons clearly

**Common Scenarios:**
- **Small Loan (K3,000):** Review docs → Approve (18% auto-set)
- **Large Loan (K10,000):** Review docs → Assign rate → Preview calculation → Approve
- **Incomplete Docs:** Reject documents → Customer re-uploads
- **Suspicious Application:** Deny with clear reason

---

## 📞 Support Information

**Technical Support:**
- Check IMPLEMENTATION_SUMMARY.md for technical details
- Check INTEGRATION_GUIDE.md for integration info
- Check this file (SYSTEM_OVERVIEW.md) for big picture

**Common Issues:**
- See Troubleshooting section in INTEGRATION_GUIDE.md
- Check error logs at: /path/to/error.log
- Verify database connectivity
- Check session configuration

---

**System Version:** 1.0
**Last Updated:** January 20, 2026
**Status:** ✅ Fully Operational

**Created by:** Claude Code Assistant
**Documentation Status:** Complete ✓
