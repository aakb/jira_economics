import reduxApi from 'redux-api';
import customFetch from './custom-fetch';
import fetch from 'cross-fetch';

export default reduxApi({
    getProjectWorklogs: {
        reducerName: 'projectWorklogs',
        url: '/jira/billing/jira_api/project_worklogs/:id'
    },
    getProjectExpenses: {
        reducerName: 'projectExpenses',
        url: '/jira/billing/jira_api/project_expenses/:id'
    },
    getToAccounts: {
        reducerName: 'toAccounts',
        url: '/jira/billing/jira_api/to_accounts'
    },
    getMaterialNumbers: {
        reducerName: 'materialNumbers',
        url: '/jira/billing/jira_api/material_numbers'
    },
    getProject: {
        reducerName: 'project',
        url: '/jira/billing/jira_api/project/:id'
    },
    getInvoice: {
        reducerName: 'invoice',
        url: '/jira/billing/jira_api/invoice/:id'
    },
    getProjectAccounts: {
        reducerName: 'accounts',
        url: '/jira/billing/jira_api/account/project/:id'
    },
    updateInvoice: {
        reducerName: 'invoice',
        url: '/jira/billing/jira_api/invoice/:id',
        options: {
            method: 'put'
        }
    },
    recordInvoice: {
        reducerName: 'invoice',
        url: '/jira/billing/jira_api/record_invoice/:id',
        options: {
            method: 'put'
        }
    },
    createInvoice: {
        reducerName: 'invoice',
        url: '/jira/billing/jira_api/invoice',
        options: {
            method: 'post'
        }
    },
    deleteInvoice: {
        reducerName: 'invoice',
        url: '/jira/billing/jira_api/invoice/:id',
        options: {
            method: 'delete'
        }
    },
    getInvoiceEntry: {
        reducerName: 'invoiceEntry',
        url: '/jira/billing/jira_api/invoice_entry/:id'
    },
    updateInvoiceEntry: {
        reducerName: 'invoiceEntry',
        url: '/jira/billing/jira_api/invoice_entry/:id',
        options: {
            method: 'put'
        }
    },
    createInvoiceEntry: {
        reducerName: 'invoiceEntry',
        url: '/jira/billing/jira_api/invoice_entry',
        options: {
            method: 'post'
        }
    },
    deleteInvoiceEntry: {
        reducerName: 'invoiceEntry',
        url: '/jira/billing/jira_api/invoice_entry/:id',
        options: {
            method: 'delete'
        }
    },
    getInvoices: {
        reducerName: 'invoices',
        url: '/jira/billing/jira_api/invoices/:id'
    },
    getAllInvoices: {
        reducerName: 'allInvoices',
        url: '/jira/billing/jira_api/invoices_all'
    },
    getInvoiceEntries: {
        reducerName: 'invoiceEntries',
        url: '/jira/billing/jira_api/invoice_entries/:id'
    },
    getAllInvoiceEntries: {
        reducerName: 'allInvoiceEntries',
        url: '/jira/billing/jira_api/invoice_entries_all'
    },
    getProjects: {
        reducerName: 'projects',
        url: '/jira/billing/jira_api/projects',
        cache: { expire: 5 * 60 }
    },
    getCurrentUser: {
        reducerName: 'currentUser',
        url: '/jira/billing/jira_api/current_user',
        cache: { expire: 5 * 60 }
    }
}).use('fetch', customFetch(fetch));
