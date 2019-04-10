import React, { Component } from 'react';
import connect from 'react-redux/es/connect/connect';
import ContentWrapper from '../components/ContentWrapper';
import PageTitle from '../components/PageTitle';
import { Link } from 'react-router';
import store from '../redux/store';
import { fetchInvoice, fetchInvoiceEntries, editInvoice } from '../redux/actions';
import PropTypes from 'prop-types';
import Button, {ButtonAppearances} from '@atlaskit/button';
import Form, {Field, FormFooter} from '@atlaskit/form';
import Spinner from '@atlaskit/spinner';
import TextField from '@atlaskit/field-text';
import Moment from 'react-moment';
import 'moment-timezone';
import rest from '../redux/utils/rest';

class Invoice extends Component {
  constructor(props) {
    super(props);

    this.handleRecordSubmit = this.handleRecordSubmit.bind(this);
    this.handleEditSubmit = this.handleEditSubmit.bind(this);
  }
  componentDidMount() {
    const {dispatch} = this.props;
    dispatch(rest.actions.getInvoice({id: `${this.props.params.invoiceId}`}));
    dispatch(rest.actions.getInvoiceEntries({id: `${this.props.params.invoiceId}`}));
  }
  // @TODO: consider cleaning up redundancy
  handleEditSubmit = (e) => {
    const {dispatch} = this.props;
    const id = this.props.params.invoiceId;
    const name = e.invoiceName;
    const recorded = this.props.invoice.data.recorded;
    const created = this.props.createdAt;
    const invoiceData = {
      id,
      name,
      recorded,
      created
    }
    dispatch(rest.actions.updateInvoice({id: `${this.props.params.invoiceId}`}, {
      body: JSON.stringify(invoiceData)
    }));
  }
  handleRecordSubmit = (e) => {
    const {dispatch} = this.props;
    const id = this.props.params.invoiceId;
    const name = this.props.invoice.data.name;
    const recorded = true;
    const created = this.props.createdAt;
    const invoiceData = {
      id,
      name,
      recorded,
      created
    }
    dispatch(rest.actions.updateInvoice({id: `${this.props.params.invoiceId}`}, {
      body: JSON.stringify(invoiceData)
    }));
  }
  render () {
    if (this.props.invoice.data.name) {
      return (
        <ContentWrapper>
          <PageTitle>Invoice</PageTitle>
          <div>ProjectID: {this.props.params.projectId}</div>
          <div>InvoiceID: {this.props.params.invoiceId}</div>
          <div>InvoiceName: {this.props.invoice.data.name}</div>
          <div>InvoiceRecorded: {String(this.props.invoice.data.recorded)}</div>
          <div>InvoiceCreated: <Moment format="YYYY-MM-DD HH:mm">{this.props.createdAt}</Moment></div>
          <div>
            <Form onSubmit={this.handleEditSubmit}>
              {({ formProps }) => (
                <form {...formProps} name="submit-edit-form">
                  <Field name="invoiceName" defaultValue={this.props.invoice.data.name} label="Enter invoice name" isRequired>
                    {({ fieldProps}) => <TextField {...fieldProps} />}
                  </Field>
                  <Button type="submit" appearance="primary">Submit</Button>
                </form>
              )}
            </Form>
          </div>
          <div>
            <Form onSubmit={this.handleRecordSubmit}>
              {({ formProps }) => (
                <form {...formProps} name="submit-recorded-form">
                  <Button type="submit" appearance="primary">Record invoice</Button>
                </form>
              )}
            </Form>
          </div>
          <div>Invoice entries:</div>
          {this.props.invoiceEntries.data.data && this.props.invoiceEntries.data.data.map((item) =>
            <div key={item.id}><Link to={`/project/${this.props.params.projectId}/${this.props.params.invoiceId}/${item.id}`}>Link til {item.name}</Link></div>
          )}
        </ContentWrapper>
      );
    }
    else {
      return (<ContentWrapper><Spinner size="large"/></ContentWrapper>);
    }
  }
}

Invoice.propTypes = {
  invoice: PropTypes.object,
  createdAt: PropTypes.string,
  invoiceEntries: PropTypes.object,
  dispatch: PropTypes.func.isRequired
};

const mapStateToProps = state => {
  let createdAt = state.invoice.data.created ? state.invoice.data.created.date : "";

  return {
    invoice: state.invoice,
    createdAt: createdAt,
    invoiceEntries: state.invoiceEntries
  };
};

export default connect(
  mapStateToProps
)(Invoice);
