import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

const resources = {
    da: {
        translation: {
            'common.modal.cancel': 'Annullér',
            'common.modal.confirm': 'Bekræft',
            'common.modal.create': 'Opret',
            'home_page.invoices': 'Fakturaer',
            'home_page.modal.title': 'Slet faktura?',
            'home_page.modal.body': 'Er du sikker på at du vil slette denne faktura?',
            'home_page.tab.not_recorded': 'Ikke bogførte',
            'home_page.tab.recorded': 'Bogførte',
            'home_page.tooltip.edit_invoice': 'Rediger faktura',
            'home_page.sr_only.edit_invoice': 'Rediger faktura',
            'home_page.tooltip.delete_invoice': 'Slet faktura',
            'home_page.sr_only.delete_invoice': 'Slet faktura',
            'home_page.tooltip.download_csv': 'Download csv',
            'home_page.sr_only.download_csv': 'Download csv',
            'home_page.tooltip.show_export_invoice': 'Vis eksport',
            'home_page.sr_only.show_export_invoice': 'Vis eksport',
            'home_page.sort': 'Sorter',
            'home_page.sorting.newest': 'Nyeste først',
            'home_page.sorting.oldest': 'Ældste først',
            'home_page.table.invoice': 'Faktura',
            'home_page.table.project': 'Projekt',
            'home_page.table.date': 'Dato',
            'home_page.table.amount': 'Beløb (DKK)',
            'home_page.table.functions': '',
            'home_page.sort.created_at': 'Oprettelsesdato',
            'home_page.filter.creator': 'Oprettet af',
            'home_page.filter.creator_option.all': 'Alle',
            'home_page.table.creator': 'Oprettet af',
            'invoice.choose_project': 'Vælg projekt',
            'invoice.modals.delete_entry.title': 'Slet fakturalinje',
            'invoice.modals.delete_entry.body': 'Er du sikker på du vil slette denne fakturalinje?',
            'invoice.modals.record.title': 'Bogfør faktura',
            'invoice.modals.record.body': 'Er du sikker på at du vil bogføre fakturaen?',
            'invoice.modals.delete.title': 'Slet faktura',
            'invoice.modals.delete.body': 'Er du sikker på at du vil slette denne faktura',
            'invoice.new': 'Ny faktura',
            'invoice.invoice_entries_list_title': 'Fakturalinjer',
            'invoice.client_information': 'Klientinformationer',
            'invoice.client_name': 'Klient',
            'invoice.client_contact': 'Kontakt',
            'invoice.client_default_price': 'Standardpris',
            'invoice.client_psp': 'PSP',
            'invoice.client_ean': 'EAN nr.',
            'invoice.edit_entry': 'Redigér linje',
            'invoice.delete_entry': 'Slét linje',
            'invoice.submit_form': 'Gem ændringer',
            'invoice.client_type': 'Type',
            'invoice.client_account': 'Konto',
            'invoice.table.to_account': 'Til konto',
            'invoice.table.product': 'Vare',
            'invoice.table.account': 'Til konto',
            'invoice.table.material_number': 'Materialenummer',
            'invoice.table.description': 'Beskrivelse',
            'invoice.table.amount': 'Antal',
            'invoice.table.price': 'Pris pr. stk (DKK)',
            'invoice.table.total_price': 'Total pris (DKK)',
            'invoice.table.type': 'Type',
            'invoice.form.select_account': 'Vælg konto',
            'home_page.table.exported_date': 'Eksporteret',
            'invoice.show_export_invoice': 'Vis faktura',
            'invoice.form.label.description': 'Beskrivelse',
            'invoice.form.label.paid_by_account': 'Betales af konto',
            'invoice.form.label.customer_account': 'Kundekonto',
            'invoice.form.helptext.description': 'Indsættes i tekstfeltet i fakturaen',
            'invoice.form.helptext.customer_account': 'Vælg hvilken kundekonto fakturaen skal udstedes til',
            'invoice.form.helptext.paid_by_account': 'Hvis valgt indsættes følgende først i tekstfeltet i fakturaen: "Betales af [KONTO]"',
            'invoice.add_from_worklog': 'Tilføj worklogs',
            'invoice.add_from_expense': 'Tilføj udgifter',
            'invoice.add_new_manual_entry': 'Tilføj manuel linje',
            'invoice.record_invoice': 'Bogfør faktura',
            'invoice.delete_invoice': 'Slet faktura',
            'invoice.recorded_false': 'Ikke bogført',
            'invoice.recorded_true': 'Bogført',
            'invoice.invoice_id': 'Faktura: <1>{{ invoiceId }}</1>',
            'invoice.invoice_recorded': 'Status: <1>{{ invoiceRecorded }}</1>',
            'invoice.date_created': 'Oprettet',
            'invoice.click_to_edit_description': 'Klik for at redigere beskrivelsen',
            'spinner.loading': 'Indlæser...',
            'invoice_entry.filter.start_date': 'Startdato',
            'invoice_entry.filter.end_date': 'Slutdato',
            'invoice_entry.filter.billed': 'Faktureret',
            'invoice_entry.filter.billed_option.all': 'Alle',
            'invoice_entry.filter.billed_option.not_billed': 'Ikke faktureret',
            'invoice_entry.filter.billed_option.billed': 'Faktureret',
            'invoice_entry.filter.worker': 'Arbejder',
            'invoice_entry.filter.worker_option.all': 'Alle',
            'invoice_entry.filter.epic': 'Epic',
            'invoice_entry.filter.epic_option.all': 'Alle',
            'invoice_entry.filter.version': 'Version',
            'invoice_entry.filter.version_option.all': 'Alle',
            'invoice_entry.filter.category': 'Alle',
            'invoice_entry.filter.category_option.all': 'Alle',
            'invoice_entry.filter.account_key': 'Konto',
            'invoice_entry.filter.account_key_option.all': 'Alle',
            'invoice_entry.table.billed': 'Faktureret',
            'invoice_entry.table.epic': 'Epic',
            'invoice_entry.table.worklog': 'Worklog',
            'invoice_entry.table.version': 'Version',
            'invoice_entry.table.user': 'Arbejder',
            'invoice_entry.table.hours_spent': 'Time brugt',
            'invoice_entry.table.updated': 'Opdateret',
            'invoice_entry.table.total_price': 'Pris',
            'invoice_entry.table.expense': 'Udgift',
            'invoice_entry.table.category': 'Kategori',
            'invoice_entry.billed_text': 'Ja',
            'invoice_entry.title': 'Udfyld fakturalinje',
            'invoice_entry.save_choices': 'Gem valg',
            'invoice_entry.choose_worklogs': 'Vælg worklogs',
            'invoice_entry.form.to_account': 'Kontonr.',
            'invoice_entry.form.product': 'Vare',
            'invoice_entry.form.product_placeholder': 'Varenavn',
            'invoice_entry.form.description': 'Beskrivelse',
            'invoice_entry.form.description_placeholder': 'Varebeskrivelse',
            'invoice_entry.form.amount': 'Antal',
            'invoice_entry.form.price': 'Pris pr. stk (DKK)',
            'invoice_entry.form.submit': 'Overfør til faktura',
            'invoice_entry.form.cancel': 'Annullér',
            'invoice_entry.form.total_price': 'Samlet pris',
            'invoice_entry.select_jira_items': 'Vælg indgange',
            'invoice_entry.form.material_number': 'Materialenummer',
            'invoice_entry.table.select_all': 'Vælg alle',
            'invoice_entry.table.deselect_all': 'Fravælg alle',
            'invoice.form.type': 'Type',
            'invoice.form.types.manual': '',
            'invoice.form.types.expense': 'Udgift',
            'invoice.form.types.worklog': 'Worklog',
            'new_invoice.filter_title': 'Vælg projekt',
            'new_invoice.filter_title_placeholder': 'Skriv for at filtrére',
            'new_invoice.create': 'Opret ny faktura',
            'new_invoice.form.name': 'Fakturaoverskrift',
            'new_invoice.form.name_placeholder': 'Vælg fakturaoverskrift',
            'new_invoice.select_customer_account': 'Kundekonto',
            'new_invoice.select_our_account': 'Modtagerkonto'
        }
    }
};

i18n
    .use(initReactI18next)
    .init({
        resources,
        lng: 'da',
        fallbackLng: 'da',
        keySeparator: false,
        interpolation: {
            escapeValue: false
        }
    });

export default i18n;
