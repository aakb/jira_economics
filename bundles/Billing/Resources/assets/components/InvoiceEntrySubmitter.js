import React, { Component } from 'react';
import connect from 'react-redux/es/connect/connect';
import ContentWrapper from './ContentWrapper';
import PageTitle from './PageTitle';
import { setSelectedIssues } from '../redux/actions';
import PropTypes from 'prop-types';
import rest from '../redux/utils/rest';

// @TODO: each invoiceEntry should be persisted with the total cost

export class InvoiceEntrySubmitter extends Component {
  constructor(props) {
    super(props);
    this.state = { account: 'Vælg PSP' };
    this.handleSelectJiraIssues = this.handleSelectJiraIssues.bind(this);
    this.onAccountChange = this.onAccountChange.bind(this);
  }

  handleSelectJiraIssues = (event) => {
    event.preventDefault();
    const { dispatch } = this.props;
    dispatch(setSelectedIssues(this.props.selectedIssues.selectedIssues));
    this.props.history.push(`/project/${this.props.match.params.projectId}/${this.props.match.params.invoiceId}/invoice_entry/jira_issues`);
  }

  getTimeSpent() {
    if (this.props.selectedIssues == undefined || this.props.selectedIssues.selectedIssues == undefined) {
      return 0;
    }
    let timeSum = 0;
    this.props.selectedIssues.selectedIssues.forEach(selectedIssue => {
      if (parseFloat(selectedIssue.timeSpent)) {
        timeSum += selectedIssue.timeSpent;
      }
    });
    return timeSum;
  }

  getUnitPrice() {
    // @TODO: replace with real data
    const unitPrices = { "PSP1": 560, "PSP2": 760, "PSP3": 820 };
    let unitPrice = 0;
    if ($('#invoice-entry-account').val()) {
      unitPrice = unitPrices[$('#invoice-entry-account').val()];
    }
    return unitPrice;
  }

  onAccountChange(event) {
    this.setState({
      account: event.target.value
    });
  }

  handleSubmit = (event) => {
    event.preventDefault();
    const { dispatch } = this.props;
    const invoiceId = this.props.match.params.invoiceId;
    // @TODO: should an invoice entry even have a name?
    const name = "dummy";
    // @TODO: get these values from the event instead
    const description = $('#invoice-entry-description').val();
    const account = $('#invoice-entry-account').val();
    const product = $('#invoice-entry-product').val();
    let jiraIssueIds = [];
    if (this.props.selectedIssues && this.props.selectedIssues.selectedIssues && this.props.selectedIssues.selectedIssues.length > 0) {
      this.props.selectedIssues.selectedIssues.forEach(selectedIssue => {
        jiraIssueIds.push(selectedIssue.id);
      });
    }
    let invoiceEntryData = {
      name,
      invoiceId,
      description,
      account,
      product,
      jiraIssueIds
    };
    const existingInvoiceEntryId = this.props.location.state.existingInvoiceEntryId;
    if (existingInvoiceEntryId) {
      invoiceEntryData.id = existingInvoiceEntryId;
      dispatch(rest.actions.updateInvoiceEntry({ id: invoiceEntryData.id }, {
        body: JSON.stringify(invoiceEntryData)
      }));
    }
    else {
      dispatch(rest.actions.createInvoiceEntry({}, {
        body: JSON.stringify(invoiceEntryData)
      }));
    }
    // @TODO: check that a new invoiceEntry was successfully created before navigating to invoice page
    dispatch(setSelectedIssues({}));
    this.props.history.push(`/project/${this.props.match.params.projectId}/${this.props.match.params.invoiceId}`);
  }

  handleCancel = (event) => {
    event.preventDefault();
    this.props.history.push(`/project/${this.props.match.params.projectId}/${this.props.match.params.invoiceId}`);
  }

  renderManualInvoiceEntryForm() {
    let pagetitle;
    // Editing an existing InvoiceEntry?
    if (this.props.location.state.existingInvoiceEntryId) {
      pagetitle = <PageTitle>Rediger manuel fakturalinje</PageTitle>;
    }
    // Creating a new InvoiceEntry?
    else {
      pagetitle = <PageTitle>Opret fakturalinje manuelt</PageTitle>;
    }
    return (
      <ContentWrapper>
        {pagetitle}
        <div>
          <form id="manual-invoice-entry-form">
            <div>
              <label htmlFor="kontonr">
                Kontonr.
              </label>
              <input
                type="text"
                name="enterKontonr"
                className="form-control"
                id="invoice-entry-account"
                aria-describedby="enterKontonr"
                placeholder="Kontonummer">
              </input>
            </div>
            <div>
              <label htmlFor="vare">
                Vare
              </label>
              <input
                type="text"
                name="enterVarenr"
                className="form-control"
                id="invoice-entry-product"
                aria-describedby="enterVarenr"
                placeholder="Varenavn">
              </input>
              <label htmlFor="beskrivelse">
                Beskrivelse
              </label>
              <input
                type="text"
                name="beskrivelse"
                className="form-control"
                id="invoice-entry-description"
                aria-describedby="enterBeskrivelse"
                placeholder="Varebeskrivelse">
              </input>
              <label htmlFor="antal">
                Antal
              </label>
              <input
                type="text"
                name="hoursSpent"
                className="form-control"
                id="invoice-entry-hours-spent"
                aria-describedby="enterHoursSpent"
                placeholder="Vareantal">
              </input>
              <label htmlFor="beskrivelse">
                Stk. pris
              </label>
              <input
                type="text"
                name="unitPrice"
                className="form-control"
                id="invoice-entry-unit-price"
                aria-describedby="enterUnitPrice"
                placeholder="0">
              </input>
            </div>
          </form>
          <form onSubmit={this.handleSubmit}>
            <button
              type="submit"
              className="btn btn-primary"
              id="create-invoice-entry">Overfør til faktura
            </button>
          </form>
          <form onSubmit={this.handleCancel}>
            <button
              type="submit"
              className="btn btn-secondary"
              id="cancel">Annuller
            </button>
          </form>
        </div>
      </ContentWrapper>
    );
  }

  renderInvoiceEntryForm() {
    let pageTitle;
    // Editing an existing InvoiceEntry?
    if (this.props.location.state.existingInvoiceEntryId) {
      pageTitle = <PageTitle>Rediger eksisterende fakturalinje</PageTitle>;
    }
    // Creating a new InvoiceEntry with JiraIssues?
    else {
      pageTitle = <PageTitle>Opret fakturalinje med issues fra Jira</PageTitle>;
    }
    return (
      <ContentWrapper>
        {pageTitle}
        <div>{Object.values(this.props.selectedIssues.selectedIssues).length + " issue(s) valgt"}</div>
        <div>{"Total timer valgt: " + this.getTimeSpent()}</div>
        <div>
          <form id="submitForm" onSubmit={this.handleSelectJiraIssues}>
            <button type="submit" className="btn btn-primary" id="submit">Rediger valg</button>
          </form>
        </div>
        <div>
          <form id="create-invoice-entry-form">
            <label htmlFor="kontonr">
              Kontonr.
            </label>
            <div>
              <select className="browser-default custom-select"
                defaultValue={this.state.account}
                id="invoice-entry-account"
                onChange={this.onAccountChange}>
                <option value="Vælg PSP" hidden>Vælg PSP</option>
                <option value="PSP1">PSP1</option>
                <option value="PSP2">PSP2</option>
                <option value="PSP3">PSP3</option>
              </select>
            </div>
            <div>
              <label htmlFor="vare">
                Vare
              </label>
              <input
                type="text"
                name="enterVarenr"
                className="form-control"
                id="invoice-entry-product"
                aria-describedby="enterVarenr"
                placeholder="Varenavn">
              </input>
              <label htmlFor="beskrivelse">
                Beskrivelse
              </label>
              <input
                type="text"
                name="beskrivelse"
                className="form-control"
                id="invoice-entry-description"
                aria-describedby="enterBeskrivelse"
                placeholder="Varebeskrivelse">
              </input>
              <label htmlFor="antal">
                Timer
              </label>
              <input
                type="text"
                name="hoursSpent"
                className="form-control"
                id="invoice-entry-hours-spent"
                aria-describedby="enterHoursSpent"
                placeholder={this.getTimeSpent()}
                readOnly>
              </input>
              <label htmlFor="beskrivelse">
                Stk. pris
              </label>
              <input
                type="text"
                name="unitPrice"
                className="form-control"
                id="invoice-entry-unit-price"
                aria-describedby="enterUnitPrice"
                placeholder={this.getUnitPrice()}
                readOnly>
              </input>
            </div>
          </form>
          <form onSubmit={this.handleSubmit}>
            <button
              type="submit"
              className="btn btn-primary"
              id="create-invoice-entry">Overfør til faktura
            </button>
          </form>
          <form onSubmit={this.handleCancel}>
            <button
              type="submit"
              className="btn btn-secondary"
              id="cancel">Annuller
            </button>
          </form>
        </div>
      </ContentWrapper>
    );
  }

  // @TODO: When editing an existing InvoiceEntry, fill form with existing values
  // @TODO: cleanup redundant HTML
  render() {
    // InvoiceEntry without JiraIssues?
    if (this.props.location.state &&
      this.props.location.state.from ===
      `/project/${this.props.match.params.projectId}/${this.props.match.params.invoiceId}`) {
      return this.renderManualInvoiceEntryForm();
    }
    // InvoiceEntry with JiraIssues?
    else if (this.props.location.state &&
      this.props.location.state.from !==
      `/project/${this.props.match.params.projectId}/${this.props.match.params.invoiceId}`) {
      return this.renderInvoiceEntryForm();
    }
    else {
      return (
        <ContentWrapper>
          <div className="spinner-border" style={{ width: '3rem', height: '3rem', role: 'status' }}>
            <span className="sr-only">Loading...</span>
          </div>
        </ContentWrapper>
      );
    }
  }
}

InvoiceEntrySubmitter.propTypes = {
  invoiceEntrySubmitter: PropTypes.object,
  dispatch: PropTypes.func.isRequired,
  selectedIssues: PropTypes.object,
  invoiceEntries: PropTypes.object
};

const mapStateToProps = state => {
  return {
    invoiceEntrySubmitter: state.invoiceEntrySubmitter,
    selectedIssues: state.selectedIssues,
    invoiceEntries: state.invoiceEntries
  };
};

export default connect(
  mapStateToProps
)(InvoiceEntrySubmitter);