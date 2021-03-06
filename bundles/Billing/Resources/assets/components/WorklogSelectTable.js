import React from 'react';
import PropTypes from 'prop-types';
import { withTranslation } from 'react-i18next';
import Moment from 'react-moment';

const WorklogSelectTable = (props) => {
    const { t } = props;

    const getNumberOfSelectedWorklogs = () => {
        return props.worklogs.reduce((carry, value) => {
            return carry + (value.selected || value.addedToOtherInvoice ? 1 : 0);
        }, 0);
    };

    const toggleSelectAll = () => {
        if (getNumberOfSelectedWorklogs() === props.worklogs.length) {
            props.worklogs.map((worklog) => {
                if (worklog.selected) {
                    props.handleSelectOnChange(worklog);
                }
            });
        } else {
            props.worklogs.map((worklog) => {
                if (!worklog.selected && !worklog.addedToOtherInvoice) {
                    props.handleSelectOnChange(worklog);
                }
            });
        }
    };

    return (
        <table className={'table'}>
            <thead>
                <tr>
                    <th>
                        <input
                            name={'selectAll'}
                            type="checkbox"
                            aria-label={ getNumberOfSelectedWorklogs() === props.worklogs.length ? t('invoice_entry.table.deselect_all') : t('invoice_entry.table.select_all') }
                            checked={ getNumberOfSelectedWorklogs() === props.worklogs.length }
                            onChange={ () => { toggleSelectAll(); } }/>
                    </th>
                    <th>{t('invoice_entry.table.worklog')}</th>
                    <th>{t('invoice_entry.table.billed')}</th>
                    <th>{t('invoice_entry.table.epic')}</th>
                    <th>{t('invoice_entry.table.version')}</th>
                    <th>{t('invoice_entry.table.user')}</th>
                    <th>{t('invoice_entry.table.hours_spent')}</th>
                    <th>{t('invoice_entry.table.worklog_started')}</th>
                </tr>
            </thead>
            <tbody>
                {
                    /* @TODO: Links to issues and worklogs in Jira */
                    props.worklogs.map((worklog) => (
                        <tr key={worklog.tempoWorklogId} className={worklog.className}>
                            <td><input
                                disabled={worklog.disabled}
                                name={'worklog-toggle-' + worklog.tempoWorklogId}
                                type="checkbox"
                                checked={ worklog.selected }
                                onChange={ () => { props.handleSelectOnChange(worklog); } }/></td>
                            <td>
                                <div>{worklog.comment} ({worklog.tempoWorklogId})</div>
                                <div><i>{worklog.issueSummary} ({worklog.issueId})</i></div>
                            </td>
                            <td>{worklog.billed}</td>
                            <td>{worklog.epicName}</td>
                            <td>{Object.keys(worklog.versions).map((versionId) => (
                                <span key={versionId} className={'p-1'}>{worklog.versions[versionId]}</span>
                            ))}</td>
                            <td>{worklog.worker}</td>
                            <td>{worklog.timeSpent}</td>
                            <td>
                                <Moment format="DD-MM-YYYY">{worklog.started}</Moment>
                            </td>
                        </tr>
                    ))
                }
            </tbody>
        </table>
    );
};

WorklogSelectTable.propTypes = {
    t: PropTypes.func.isRequired,
    worklogs: PropTypes.array.isRequired,
    handleSelectOnChange: PropTypes.func.isRequired
};

export default withTranslation()(WorklogSelectTable);
