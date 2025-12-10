# Page snapshot

```yaml
- main [ref=e5]:
  - main [ref=e8]:
    - generic [ref=e11]:
      - img "cbApp Logo" [ref=e13]
      - generic [ref=e15]:
        - generic [ref=e18]:
          - generic [ref=e19]:
            - generic [ref=e22]:
              - generic: Username or Email
              - textbox "Username or Email" [ref=e23]
            - alert [ref=e24]
          - generic [ref=e26]:
            - generic [ref=e29]:
              - generic: Password
              - textbox "Password" [ref=e30]
            - alert [ref=e31]
          - button "Login" [disabled]:
            - generic: Login
          - generic [ref=e33]:
            - text: Noch kein Konto?
            - link "Hier registrieren" [ref=e34] [cursor=pointer]:
              - /url: /auth/register
        - generic [ref=e36]:
          - generic [ref=e37]: 󰀎
          - text: Quick Login (Development)
      - generic [ref=e39]:
        - paragraph [ref=e40]: © 2025 cbApp.ch - Alle Rechte vorbehalten
        - button "Dark Mode" [ref=e42] [cursor=pointer]:
          - generic [ref=e43]:
            - generic [ref=e44]: 󰽥
            - text: Dark Mode
```