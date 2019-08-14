import React, { Component } from 'react';
import connect from 'react-redux/es/connect/connect';
import PageTitle from '../components/PageTitle';
import PropTypes from 'prop-types';
import Moment from 'react-moment';
import 'moment-timezone';
import rest from '../redux/utils/rest';
import ContentWrapper from '../components/ContentWrapper';
import Tabs from 'react-bootstrap/Tabs';
import Tab from 'react-bootstrap/Tab';
import Table from 'react-bootstrap/Table';
import Form from 'react-bootstrap/Form';
import ButtonGroup from 'react-bootstrap/ButtonGroup';
import Button from 'react-bootstrap/Button';
import OverlayTrigger from 'react-bootstrap/OverlayTrigger';
import Tooltip from 'react-bootstrap/Tooltip';
import Modal from 'react-bootstrap/Modal';
import Spinner from '../components/Spinner';

// @TODO: sort invoices by date on initial load

class HomePage extends Component {
    constructor (props) {
        super(props);
        this.handleModalShow = this.handleModalShow.bind(this);
        this.handleModalClose = this.handleModalClose.bind(this);
        this.state = {
            allInvoices: {},
            allInvoiceEntries: {},
            showModal: false,
            invoiceIdToDelete: -1
        };
    };

    componentDidMount () {
        const { dispatch } = this.props;
        dispatch(rest.actions.getAllInvoices())
            .then((response) => {
                this.setState({ allInvoices: response });
            })
            .catch((reason) => console.log('isCanceled', reason.isCanceled));
        dispatch(rest.actions.getAllInvoiceEntries())
            .then((response) => {
                this.setState({ allInvoiceEntries: response });
            })
            .catch((reason) => console.log('isCanceled', reason.isCanceled));
    };

    handleModalClose = (event) => {
        event.preventDefault();
        var invoiceId = this.state.invoiceIdToDelete;
        if (event.target.id === 'delete-btn') {
            const { dispatch } = this.props;
            dispatch(rest.actions.deleteInvoice({ id: invoiceId }))
                .then(() => {
                    this.removeInvoiceFromState(invoiceId);
                })
                .catch((reason) => console.log('isCanceled', reason.isCanceled));
            // @TODO: Check that deletion is successful
        }
        this.setState({ showModal: false, invoiceIdToDelete: -1 });
    };

    handleInvoiceDelete = (event, invoiceId) => {
        event.preventDefault();
        this.setState({ invoiceIdToDelete: invoiceId });
        this.handleModalShow();
    };

    handleModalShow () {
        this.setState({ showModal: true });
    };

    removeInvoiceFromState (invoiceId) {
        let filteredInvoices = this.state.allInvoices.data.filter((invoice) => {
            return invoiceId !== invoice.invoiceId;
        });
        let remainingInvoices = { 'data': filteredInvoices };
        this.setState({ allInvoices: remainingInvoices });
    };

    getPriceForInvoice (invoiceId) {
        if (!this.state.allInvoiceEntries.data) {
            return 0;
        }
        let invoiceEntries = this.state.allInvoiceEntries.data.filter((invoiceEntry) => {
            return invoiceEntry.invoiceId === invoiceId;
        });
        if (invoiceEntries === undefined) {
            return 'N/A';
        }
        let totalPrice = 0;
        invoiceEntries.forEach(invoiceEntry => {
            totalPrice += invoiceEntry.price;
        });
        return totalPrice.toFixed(2);
    };

    onSort (event) {
        let sortOrder = 'desc';
        if (event.target.value === 'Ældste først') {
            sortOrder = 'asc';
        }
        let sortedInvoices = this.state.allInvoices.data.sort(function (i1, i2) {
            if (sortOrder === 'asc') {
                return i1.created.date > i2.created.date;
            } else {
                return i1.created.date < i2.created.date;
            }
        });
        sortedInvoices = { 'data': sortedInvoices };
        this.setState({ allInvoices: sortedInvoices });
    };

    render () {
        if (this.state.allInvoices.data && this.state.allInvoiceEntries.data) {
            return (
                <ContentWrapper>
                    <PageTitle breadcrumb="">Fakturaer</PageTitle>
                    <Tabs defaultActiveKey="drafts"
                        id="uncontrolled-tab-example">
                        <Tab eventKey="drafts" title="Kladder">
                            <Form className="mt-3 mb-1 w-25">
                                <Form.Group className="mb-0">
                                    <Form.Label className="sr-only">Sorter</Form.Label>
                                    <Form.Control size="sm" as="select" onChange={this.onSort.bind(this)}>
                                        <option>Nyeste først</option>
                                        <option>Ældste først</option>
                                    </Form.Control>
                                </Form.Group>
                            </Form>
                            <Table responsive striped hover borderless>
                                <thead>
                                    <tr>
                                        <th>Fakturanavn</th>
                                        <th>Projekt</th>
                                        <th>Fakturadato</th>
                                        <th>Beløb (DKK)</th>
                                        <th className="text-right">Funktion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {this.state.allInvoices.data && this.state.allInvoices.data
                                        .filter((item) => {
                                            return item.recorded === false;
                                        })
                                        .map((item) =>
                                            <tr key={item.invoiceId}>
                                                <td><a
                                                    href={'/jira/billing/project/' + item.jiraProjectId + '/' + item.invoiceId}><strong>{item.invoiceName}</strong></a>
                                                </td>
                                                <td>{item.jiraProjectName}</td>
                                                <td><Moment
                                                    format="DD-MM-YYYY">{item.created.date}</Moment>
                                                </td>
                                                <td>
                                                    <strong>{this.getPriceForInvoice(item.invoiceId)}</strong>
                                                </td>
                                                <td className="text-right">
                                                    <ButtonGroup size="sm"
                                                        className="float-right"
                                                        aria-label="Invoice functions">
                                                        <OverlayTrigger
                                                            key="edit"
                                                            placement="top"
                                                            overlay={
                                                                <Tooltip
                                                                    id="tooltip-edit">
                                                                Edit this invoice
                                                                </Tooltip>
                                                            }
                                                        >
                                                            <Button
                                                                className="btn-primary"
                                                                href={'/jira/billing/project/' + item.jiraProjectId + '/' + item.invoiceId}>
                                                                <i className="fas fa-edit mx-2"></i>
                                                                <span
                                                                    className="sr-only">rediger</span>
                                                            </Button>
                                                        </OverlayTrigger>
                                                        <OverlayTrigger
                                                            key="delete"
                                                            placement="top"
                                                            overlay={
                                                                <Tooltip
                                                                    id="tooltip-delete">
                                                                Delete this invoice
                                                                </Tooltip>
                                                            }
                                                        >
                                                            <Button
                                                                className="btn-danger"
                                                                onClick={this.handleInvoiceDelete.bind(this, item.invoiceId)}>
                                                                <i className="fas fa-trash-alt mx-2"></i>
                                                                <span
                                                                    className="sr-only">slet</span>
                                                            </Button>
                                                        </OverlayTrigger>

                                                    </ButtonGroup>
                                                </td>
                                            </tr>
                                        )}
                                </tbody>
                            </Table>
                        </Tab>
                        <Tab eventKey="posted" title="Bogførte">
                            <Form className="float-right">
                                <Form.Group>
                                    <Form.Label>Sorter</Form.Label>
                                    <Form.Control as="select" onChange={this.onSort.bind(this)}>
                                        <option>Nyeste først</option>
                                        <option>Ældste først</option>
                                    </Form.Control>
                                </Form.Group>
                            </Form>
                            <Table responsive striped hover borderless>
                                <thead>
                                    <tr>
                                        <th>Fakturanavn</th>
                                        <th>Projekt</th>
                                        <th>Fakturadato</th>
                                        <th>Beløb (DKK)</th>
                                        <th className="text-right">Funktion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {this.state.allInvoices.data && this.state.allInvoices.data
                                        .filter((item) => {
                                            return item.recorded === true;
                                        })
                                        .map((item) =>
                                            <tr key={item.invoiceId}>
                                                <td><a
                                                    href={'/jira/billing/project/' + item.jiraProjectId + '/' + item.invoiceId}><strong>{item.invoiceName}</strong></a>
                                                </td>
                                                <td>{item.jiraProjectName}</td>
                                                <td><Moment
                                                    format="DD-MM-YYYY">{item.created.date}</Moment>
                                                </td>
                                                <td>
                                                    <strong>{this.getPriceForInvoice(item.invoiceId)}</strong>
                                                </td>
                                                <td className="text-right">
                                                    <ButtonGroup
                                                        className="btn-group-sm float-right"
                                                        aria-label="Invoice functions">
                                                        <OverlayTrigger
                                                            key="download-csv"
                                                            placement="top"
                                                            overlay={
                                                                <Tooltip
                                                                    id="tooltip-download-csv">
                                                                Download csv file
                                                                </Tooltip>
                                                            }
                                                        >
                                                            <Button>
                                                                <i className="fas fa-file-csv mx-2"></i>
                                                                <span
                                                                    className="sr-only">hent csv</span>
                                                            </Button>
                                                        </OverlayTrigger>
                                                    </ButtonGroup>
                                                </td>
                                            </tr>
                                        )}
                                </tbody>
                            </Table>
                        </Tab>
                    </Tabs>
                    <Modal show={this.state.showModal}
                        onHide={this.handleModalClose}>
                        <Modal.Header>
                            <Modal.Title>Confirm deletion</Modal.Title>
                        </Modal.Header>
                        <Modal.Body>Are you sure you want to delete this invoice?</Modal.Body>
                        <Modal.Footer>
                            <Button variant="secondary"
                                onClick={this.handleModalClose}>
                                Cancel
                            </Button>
                            <Button id="delete-btn" variant="danger"
                                onClick={this.handleModalClose}>
                                Delete
                            </Button>
                        </Modal.Footer>
                    </Modal>
                </ContentWrapper>
            );
        } else {
            return (
                <ContentWrapper>
                    <Spinner/>
                </ContentWrapper>
            );
        }
    }
}

HomePage.propTypes = {
    allInvoices: PropTypes.object,
    allInvoiceEntries: PropTypes.object,
    dispatch: PropTypes.func.isRequired
};

const mapStateToProps = state => {
    return {
        allInvoices: state.allInvoices,
        allInvoiceEntries: state.allInvoiceEntries
    };
};

export default connect(
    mapStateToProps
)(HomePage);
