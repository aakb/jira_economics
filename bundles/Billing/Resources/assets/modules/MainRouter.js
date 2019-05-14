import PropTypes from 'prop-types';
import React, { Component } from 'react';
import { BrowserRouter as Router, Route } from "react-router-dom";
import App from './App';
import HomePage from '../pages/HomePage';
import Project from '../components/Project';
import Invoice from '../components/Invoice';
import InvoiceEntry from '../components/InvoiceEntry';
import JiraIssues from '../components/JiraIssues';
import InvoiceEntrySubmitter from '../components/InvoiceEntrySubmitter';

export default class MainRouter extends Component {
  constructor() {
    super();
    this.state = {
      navOpenState: {
        isOpen: true,
        width: 304,
      }
    }
  }

  getChildContext () {
    return {
      navOpenState: this.state.navOpenState,
    };
  }

  appWithPersistentNav = () => (props) => (
    <App
      onNavResize={this.onNavResize}
      {...props}
    />
  );

  onNavResize = (navOpenState) => {
    this.setState({
      navOpenState,
    });
  };

  render() {
    return (
      <Router basename={"/billing"}>
        <Route exact path="/" component={HomePage}/>
        <Route exact path="/project/:projectId" component={Project} />
        <Route exact path="/project/:projectId/entry/:invoiceEntryId" component={Project}/>
        <Route exact path="/project/:projectId/:invoiceId" component={Invoice}/>
        <Route exact path="/project/:projectId/:invoiceId/jiraIssues" component={JiraIssues}/>
        <Route exact path="/project/:projectId/:invoiceId/invoice_entry" component={InvoiceEntrySubmitter}/>
        <Route exact path="/project/:projectId/:invoiceId/:invoiceEntryId" component={InvoiceEntry}/>
      </Router>
    );
  }
}

MainRouter.childContextTypes = {
  navOpenState: PropTypes.object
};
