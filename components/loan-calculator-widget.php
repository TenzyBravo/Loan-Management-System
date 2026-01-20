<!-- Vue.js 3 Loan Calculator Widget -->
<div id="loanCalculatorApp" class="card dashboard-card">
  <div class="card-header">
    <h5 class="mb-0">
      <i class="fa fa-calculator text-primary"></i>
      Interactive Loan Calculator
    </h5>
  </div>
  <div class="card-body">
    <div class="row">
      <!-- Input Section -->
      <div class="col-md-6">
        <div class="form-group">
          <label for="loanAmount">Loan Amount (K)</label>
          <input
            type="number"
            class="form-control"
            id="loanAmount"
            v-model.number="loanAmount"
            @input="calculate"
            min="1000"
            step="1000"
            placeholder="Enter loan amount">
        </div>

        <div class="form-group">
          <label for="interestRate">Interest Rate (% per year)</label>
          <input
            type="number"
            class="form-control"
            id="interestRate"
            v-model.number="interestRate"
            @input="calculate"
            min="0"
            max="100"
            step="0.5"
            placeholder="Enter interest rate">
        </div>

        <div class="form-group">
          <label for="loanTerm">Loan Term (months)</label>
          <select
            class="form-control"
            id="loanTerm"
            v-model.number="loanTerm"
            @change="calculate">
            <option value="6">6 months</option>
            <option value="12">12 months</option>
            <option value="18">18 months</option>
            <option value="24">24 months</option>
            <option value="36">36 months</option>
            <option value="48">48 months</option>
            <option value="60">60 months</option>
          </select>
        </div>

        <div class="form-group">
          <label for="paymentFrequency">Payment Frequency</label>
          <select
            class="form-control"
            id="paymentFrequency"
            v-model="paymentFrequency"
            @change="calculate">
            <option value="monthly">Monthly</option>
            <option value="weekly">Weekly</option>
            <option value="biweekly">Bi-weekly</option>
          </select>
        </div>
      </div>

      <!-- Results Section -->
      <div class="col-md-6">
        <div class="calculation-results">
          <div class="result-item">
            <div class="result-label">Monthly Payment</div>
            <div class="result-value text-primary">K {{ formatCurrency(monthlyPayment) }}</div>
          </div>

          <div class="result-item">
            <div class="result-label">Total Interest</div>
            <div class="result-value text-warning">K {{ formatCurrency(totalInterest) }}</div>
          </div>

          <div class="result-item">
            <div class="result-label">Total Amount</div>
            <div class="result-value text-success">K {{ formatCurrency(totalAmount) }}</div>
          </div>

          <div class="result-item">
            <div class="result-label">Number of Payments</div>
            <div class="result-value text-info">{{ numberOfPayments }}</div>
          </div>

          <!-- Payment Breakdown Chart -->
          <div class="mt-4">
            <canvas id="paymentBreakdownChart" height="200"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Amortization Schedule Preview -->
    <div class="mt-4" v-if="amortizationSchedule.length > 0">
      <h6 class="mb-3">
        <i class="fa fa-table"></i>
        Payment Schedule Preview (First 5 payments)
      </h6>
      <div class="table-responsive">
        <table class="table table-sm table-hover">
          <thead>
            <tr>
              <th>#</th>
              <th>Payment Date</th>
              <th>Payment</th>
              <th>Principal</th>
              <th>Interest</th>
              <th>Balance</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(payment, index) in amortizationSchedule.slice(0, 5)" :key="index">
              <td>{{ index + 1 }}</td>
              <td>{{ payment.date }}</td>
              <td class="font-weight-bold">K {{ formatCurrency(payment.payment) }}</td>
              <td>K {{ formatCurrency(payment.principal) }}</td>
              <td>K {{ formatCurrency(payment.interest) }}</td>
              <td>K {{ formatCurrency(payment.balance) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <p class="text-muted text-center mb-0">
        <small>Showing first 5 of {{ numberOfPayments }} payments</small>
      </p>
    </div>
  </div>
</div>

<style>
.calculation-results {
  background: linear-gradient(135deg, #f9fafb 0%, #ffffff 100%);
  border-radius: 0.75rem;
  padding: 1.5rem;
}

.result-item {
  padding: 1rem;
  margin-bottom: 1rem;
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  border-left: 4px solid var(--primary-blue, #2563eb);
}

.result-item:last-child {
  margin-bottom: 0;
}

.result-label {
  font-size: 0.875rem;
  color: var(--gray-600, #4b5563);
  margin-bottom: 0.25rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.result-value {
  font-size: 1.75rem;
  font-weight: 700;
}
</style>

<script>
(function() {
  const { createApp } = Vue;

  const loanCalculatorApp = createApp({
    data: function() {
      return {
        loanAmount: 50000,
        interestRate: 15,
        loanTerm: 12,
        paymentFrequency: 'monthly',
        monthlyPayment: 0,
        totalInterest: 0,
        totalAmount: 0,
        numberOfPayments: 0,
        amortizationSchedule: [],
        chart: null
      };
    },
    mounted: function() {
      this.calculate();
    },
    methods: {
      calculate: function() {
        if (!this.loanAmount || !this.interestRate || !this.loanTerm) {
          return;
        }

        // Calculate based on payment frequency
        var periodsPerYear = this.paymentFrequency === 'monthly' ? 12 :
                           this.paymentFrequency === 'weekly' ? 52 : 26;

        var numberOfPayments = Math.ceil(this.loanTerm * (periodsPerYear / 12));
        var periodInterestRate = (this.interestRate / 100) / periodsPerYear;

        // Calculate payment using amortization formula
        var payment = this.loanAmount *
          (periodInterestRate * Math.pow(1 + periodInterestRate, numberOfPayments)) /
          (Math.pow(1 + periodInterestRate, numberOfPayments) - 1);

        this.monthlyPayment = payment;
        this.numberOfPayments = numberOfPayments;
        this.totalAmount = payment * numberOfPayments;
        this.totalInterest = this.totalAmount - this.loanAmount;

        // Generate amortization schedule
        this.generateAmortizationSchedule(payment, periodInterestRate, numberOfPayments);

        // Update chart
        this.updateChart();
      },

      generateAmortizationSchedule: function(payment, rate, periods) {
        var schedule = [];
        var balance = this.loanAmount;
        var today = new Date();

        for (var i = 0; i < periods; i++) {
          var interestPayment = balance * rate;
          var principalPayment = payment - interestPayment;
          balance = balance - principalPayment;

          // Calculate payment date
          var paymentDate = new Date(today);
          if (this.paymentFrequency === 'monthly') {
            paymentDate.setMonth(today.getMonth() + i + 1);
          } else if (this.paymentFrequency === 'weekly') {
            paymentDate.setDate(today.getDate() + (i + 1) * 7);
          } else { // biweekly
            paymentDate.setDate(today.getDate() + (i + 1) * 14);
          }

          schedule.push({
            payment: payment,
            principal: principalPayment,
            interest: interestPayment,
            balance: Math.max(0, balance),
            date: paymentDate.toISOString().split('T')[0]
          });
        }

        this.amortizationSchedule = schedule;
      },

      updateChart: function() {
        var self = this;
        var ctx = document.getElementById('paymentBreakdownChart');
        if (!ctx) return;

        // Destroy existing chart
        if (this.chart) {
          this.chart.destroy();
        }

        // Create new chart
        this.chart = new Chart(ctx.getContext('2d'), {
          type: 'doughnut',
          data: {
            labels: ['Principal', 'Interest'],
            datasets: [{
              data: [self.loanAmount, self.totalInterest],
              backgroundColor: ['#2563eb', '#f59e0b'],
              borderWidth: 0
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
              legend: {
                position: 'bottom',
                labels: {
                  padding: 15,
                  usePointStyle: true,
                  font: { size: 12, weight: '500' }
                }
              },
              tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                callbacks: {
                  label: function(context) {
                    var label = context.label || '';
                    var value = context.parsed || 0;
                    var total = context.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                    var percentage = ((value / total) * 100).toFixed(1);
                    return label + ': K ' + value.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,') +
                           ' (' + percentage + '%)';
                  }
                }
              }
            }
          }
        });
      },

      formatCurrency: function(value) {
        if (!value && value !== 0) return '0.00';
        return parseFloat(value).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
      }
    }
  });

  // Wait for DOM and Chart.js to be ready
  $(document).ready(function() {
    // Wait a bit for Chart.js to load
    setTimeout(function() {
      if (typeof Chart !== 'undefined') {
        loanCalculatorApp.mount('#loanCalculatorApp');
      } else {
        console.error('Chart.js not loaded');
      }
    }, 100);
  });
})();
</script>
