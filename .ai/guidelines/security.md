# Security

- Never take a user ID from MCP tool parameters; use the authenticated user.
- Hiding an MCP tool with `shouldRegister()` is not enough; authorize on execution as well.
