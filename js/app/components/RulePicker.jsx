import React from 'react'
import PropTypes from 'prop-types'
import i18n from '../i18n'
import RulePickerLine from './RulePickerLine.jsx'
import parsePricingRule from '../delivery/pricing-rule-parser'

const lineToString = state => {
  /*
  Build the expression line from the user's input stored in state.
  Returns nothing if we can't build the line.
  */

  if (state.operator === 'in' && Array.isArray(state.right) && state.right.length === 2) {
    return `${state.left} in ${state.right[0]}..${state.right[1]}`
  }

  if (state.left === 'diff_days(pickup)') {
    return `diff_days(pickup) ${state.operator} ${state.right}`
  }

  switch (state.operator) {
  case '<':
  case '>':
    return `${state.left} ${state.operator} ${state.right}`
  case 'in_zone':
  case 'out_zone':
    return `${state.operator}(${state.left}, "${state.right}")`
  case '==':
    return `${state.left} == "${state.right}"`
  }
}

const linesToString = lines => lines.map(line => lineToString(line)).join(' and ')

class RulePicker extends React.Component {

  constructor (props) {
    super(props)

    this.state = {
      lines: parsePricingRule(this.props.expression),
      // This is used as a "revision counter",
      // to create an accurate React key prop
      rev: 0
    }

    this.addLine = this.addLine.bind(this)
    this.updateLine = this.updateLine.bind(this)
    this.deleteLine = this.deleteLine.bind(this)
  }

  addLine (evt) {
    evt.preventDefault()
    let lines = this.state.lines.slice()
    lines.push({
      type: '',
      operator: '',
      value: ''
    })
    this.setState({
      lines,
      rev: ++this.state.rev
    })
  }

  deleteLine(index) {
    let lines = this.state.lines.slice()
    lines.splice(index, 1)
    this.setState({
      lines,
      rev: ++this.state.rev
    })
  }

  updateLine(index, line) {
    let lines = this.state.lines.slice()
    lines.splice(index, 1, line)
    this.setState({ lines })
  }

  componentDidUpdate () {
    this.props.onExpressionChange(linesToString(this.state.lines))
  }

  render () {

    return (
      <div className="rule-picker">
        { this.state.lines.map((line, index) => (
          <RulePickerLine
            key={ `${index}-${this.state.rev}` }
            index={ index }
            type={ line.left }
            operator={ line.operator }
            value={ line.right }
            zones={ this.props.zones }
            onUpdate={ this.updateLine }
            onDelete={ this.deleteLine } />
        )) }
        <div className="row">
          <div className="col-xs-12 text-right">
            <button className="btn btn-xs btn-primary" onClick={this.addLine}>
              <i className="fa fa-plus"></i> { i18n.t('RULE_PICKER_ADD_CONDITION') }
            </button>
          </div>
        </div>
        <div className="row rule-picker-preview">
          <pre>{ linesToString(this.state.lines) }</pre>
        </div>
      </div>
    )
  }
}

RulePicker.propTypes = {
  expression: PropTypes.string.isRequired,
  onExpressionChange: PropTypes.func.isRequired,
  zones: PropTypes.arrayOf(PropTypes.string)
}

export default RulePicker
